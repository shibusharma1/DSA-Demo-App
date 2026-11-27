<?php

namespace App\Services\busy;

use App\Services\BusyApiService;
use Carbon\Carbon;
use DOMDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DsaToBusyOrder
{
    public function __construct(
        private readonly BusyApiService $busyApiService
    ) {}

    /** Push an order using the actual DSA tables; no Eloquent relationships. */
    public function pushOrder(int $orderId): array
    {
        $result = [
            'success' => false, 'order_id' => $orderId,
            'busyorder_id' => null, 'message' => null, 'error' => null,
        ];

        try {
            $order = DB::table('orders')->where('id', $orderId)
                ->whereNull('deleted_at')->first();
            if (!$order) {
                throw new \RuntimeException('Order not found.');
            }
            if (empty($order->order_date) || empty($order->order_no)) {
                throw new \RuntimeException('Order date or number missing.');
            }
            $client = DB::table('parties_busy')->where('id', $order->client_id)
                ->where('company_id', $order->company_id)->first();
            if (!$client || empty($client->busycustomer_id)) {
                throw new \RuntimeException('Customer is not mapped to BUSY.');
            }

            $details = DB::table('orderproducts as od')
                ->leftJoin('products as p', function ($join) use ($order) {
                    $join->on('p.id', '=', 'od.product_id')
                        ->where('p.company_id', '=', $order->company_id);
                })
                ->leftJoin('unit_types as ut', function ($join) use ($order) {
                    $join->on('ut.id', '=', 'od.unit')
                        ->where('ut.company_id', '=', $order->company_id);
                })
                ->where('od.order_id', $orderId)
                ->whereNull('od.deleted_at')
                ->orderBy('od.id')
                ->select([
                    'od.id', 'od.order_id', 'od.product_id', 'od.unit as unit_id',
                    'od.rate', 'od.quantity', 'od.amount', 'od.ptotal_amt',
                    'od.pdiscount as discount', 'od.pdiscount_type as discount_type',
                    'od.short_desc as description',
                    'p.id as product__id', 'p.busyproduct_id', 'p.product_name',
                    'ut.id as unit__id', 'ut.busyunit_id',
                    'ut.name as unit_name', 'ut.symbol as unit_symbol',
                ])->get();
            if ($details->isEmpty()) {
                throw new \RuntimeException('Order does not contain any products.');
            }

            $productTaxes = DB::table('tax_on_orderproducts as tp')
                ->leftJoin('tax_types as tt', function ($join) use ($order) {
                    $join->on('tt.id', '=', 'tp.tax_type_id')
                        ->where('tt.company_id', '=', $order->company_id);
                })
                ->whereIn('tp.orderproduct_id', $details->pluck('id')->all())
                ->select('tp.orderproduct_id', 'tp.tax_type_id', 'tt.id',
                    'tt.name', 'tt.display_name', 'tt.busytax_id', 'tt.percent', 'tt.chained_to')
                ->get()->groupBy('orderproduct_id');

            $orderTaxes = DB::table('tax_on_orders')
                ->where('order_id', $orderId)->get();

            // This XML has a single ItemTaxCategory per item. Do not guess
            // how order-level or multiple/chained taxes map into BUSY.
            if (!(int) $order->product_level_tax_flag &&
                ($orderTaxes->isNotEmpty() || abs((float) $order->tax) > 0.005)) {
                throw new \RuntimeException('Order-level tax requires a verified BUSY tax mapping.');
            }
            $lineTaxTotal = 0.0;
            $lineNetTotal = 0.0;
            foreach ($details as $detail) {
                if (!$detail->product__id || empty($detail->busyproduct_id)) {
                    throw new \RuntimeException("Product #{$detail->product_id} is not mapped to BUSY.");
                }
                if ($detail->unit_id && (!$detail->unit__id || empty($detail->busyunit_id))) {
                    throw new \RuntimeException("Unit not mapped for product #{$detail->product_id}.");
                }
                $detail->product = (object) ['id' => $detail->product__id,
                    'busyproduct_id' => $detail->busyproduct_id, 'product_name' => $detail->product_name];
                $detail->unit = $detail->unit_id ? (object) ['id' => $detail->unit__id,
                    'busyunit_id' => $detail->busyunit_id, 'name' => $detail->unit_name,
                    'symbol' => $detail->unit_symbol] : null;
                if (!is_numeric($detail->quantity) || !is_numeric($detail->rate) ||
                    (float) $detail->quantity <= 0 || (float) $detail->rate < 0) {
                    throw new \RuntimeException("Invalid quantity/rate for line #{$detail->id}.");
                }
                $gross = round((float) $detail->quantity * (float) $detail->rate, 2);
                $discount = (int) $order->product_level_discount_flag
                    ? (float) $detail->discount : 0.0;
                $discountAmount = $this->discountAmount($gross, $discount, $detail->discount_type);
                $detail->discount_percent = $gross > 0 ? $discountAmount * 100 / $gross : 0;
                $detail->taxable_amount = round($gross - $discountAmount, 2);
                $taxes = $productTaxes->get($detail->id, collect());
                $detail->tax = null;
                $detail->tax_rate = 0;
                $detail->tax_amount = 0;
                if ((int) $order->product_level_tax_flag) {
                    if ($taxes->count() > 1) {
                        throw new \RuntimeException("Multiple taxes on line #{$detail->id}; BUSY combined category must be verified.");
                    }
                    if ($taxes->isNotEmpty()) {
                        $tax = $taxes->first();
                        if (!$tax->id || empty($tax->busytax_id) || !empty($tax->chained_to) || !is_numeric($tax->percent)) {
                            throw new \RuntimeException("Missing or chained BUSY tax mapping on line #{$detail->id}.");
                        }
                        $detail->tax = $tax;
                        $detail->tax_rate = (float) $tax->percent;
                        $detail->tax_amount = round($detail->taxable_amount * $detail->tax_rate / 100, 2);
                    }
                }
                $lineTaxTotal += $detail->tax_amount;
                $lineNetTotal += $detail->taxable_amount;
            }

            // Guard the assumed calculation against the persisted order totals.
            if (abs($lineTaxTotal - (float) $order->tax) > 0.011) {
                throw new \RuntimeException('Calculated line tax differs from saved order tax.');
            }
            $order->busy_discount_amount = $this->discountAmount($lineNetTotal,
                (float) $order->discount, $order->discount_type);
            $expectedTotal = round($lineNetTotal + $lineTaxTotal - $order->busy_discount_amount, 2);
            if (abs($expectedTotal - (float) $order->grand_total) > 0.011) {
                throw new \RuntimeException('Calculated voucher total differs from saved grand total. Check discount, tax and delivery charges.');
            }
            $order->client = $client;
            $order->details = $details;
            $xml = $this->buildSaleXml($order);
            Log::channel('busy_logs')->info('BUSY Sale XML Generated', ['order_id' => $orderId, 'xml' => $xml]);

            $response = !empty($order->busyorder_id)
                ? $this->busyApiService->modifySaleVoucher($xml)
                : $this->busyApiService->createSaleVoucher($xml);
            if (!($response['success'] ?? false)) {
                throw new \RuntimeException($response['description'] ?? 'BUSY Sale Voucher sync failed.');
            }
            $busyOrderId = $this->extractBusyVoucherId($response['body'] ?? '');
            if (!$busyOrderId) {
                throw new \RuntimeException('BUSY response has no voucher ID. Verify remotely before retrying to avoid duplication.');
            }
            DB::table('orders')->where('id', $orderId)
                ->where('company_id', $order->company_id)
                ->update(['busyorder_id' => $busyOrderId, 'updated_at' => now()]);
            $result['success'] = true;
            $result['busyorder_id'] = $busyOrderId;
            $result['message'] = 'Order successfully synchronized with BUSY.';
        } catch (Throwable $e) {
            $result['error'] = $e->getMessage();
            Log::channel('busy_logs')->error('BUSY Sale Sync Failed', [
                'order_id' => $orderId, 'message' => $e->getMessage(),
            ]);
        }
        return $result;
    }

    /** Assumed DSA discount convention: percent or fixed amount. */
    private function discountAmount(float $base, float $discount, $type): float
    {
        if ($discount == 0.0) {
            return 0.0;
        }
        $type = strtolower(trim((string) $type));
        if (in_array($type, ['percent', 'percentage', '%'], true)) {
            $amount = $base * $discount / 100;
        } elseif (in_array($type, ['amount', 'fixed', 'flat'], true)) {
            $amount = $discount;
        } else {
            throw new \RuntimeException('Unsupported discount type: ' . $type);
        }
        if ($amount < 0 || $amount > $base) {
            throw new \RuntimeException('Discount is outside the valid amount range.');
        }
        return round($amount, 2);
    }

    /** Build BUSY Sale XML. */
    private function buildSaleXml(object $order): string
    {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = false;
        /* Root */
        $sale = $xml->createElement('Sale');
        $xml->appendChild($sale);
        /* Voucher Series */
        $this->appendText($xml, $sale, 'VchSeriesName', 'Main');
        /*Date*/
        $this->appendText($xml, $sale, 'Date', Carbon::parse($order->order_date)->format('d-m-Y'));

        /*Voucher Type */
        $this->appendText($xml, $sale, 'VchType', '9');

        /*Stock Updation Date*/
        $this->appendText($xml, $sale, 'StockUpdationDate', Carbon::parse($order->order_date)->format('d-m-Y'));

        /*Voucher Number */
        $this->appendText($xml, $sale, 'VchNo', (string) $order->order_no);

        /*Sales Type*/
        $this->appendText($xml, $sale, 'STPTName', 'Local-ItemWise');

        /*Customer*/
        $this->appendText($xml, $sale, 'MasterName1', $this->getClientBusyName($order));

        /*Transaction Currency */
        $this->appendText($xml, $sale, 'TranCurName', 'Rs.');

        /*Input Type */
        $this->appendText($xml, $sale, 'InputType', '1');

        /*Billing Details*/
        $this->buildBillingDetails($xml, $sale, $order);

        /*Other Information */
        $this->buildOtherInformation($xml, $sale, $order);

        /*Item Entries */
        $this->buildItemEntries($xml, $sale, $order);

        /*Bill Sundries*/
        $this->buildBillSundries($xml, $sale, $order);

        /*Pending Bill Details*/
        $this->buildPendingBillDetails($xml, $sale, $order);

        return $xml->saveXML($xml->documentElement);
    }

    /**
     * Build BillingDetails.
     */
    private function buildBillingDetails(DOMDocument $xml, \DOMElement $sale, object $order): void
    {
        $billing = $xml->createElement('BillingDetails');
        $this->appendText($xml, $billing, 'PartyName', $this->getClientBusyName($order));
        $client = $order->client;
        $this->appendText($xml, $billing, 'MobileNo', $client->mobile ?? '');
        $sale->appendChild($billing);
    }

    /**
     * Build VchOtherInfoDetails.
     */
    private function buildOtherInformation(DOMDocument $xml, \DOMElement $sale, object $order): void
    {
        $otherInfo = $xml->createElement('VchOtherInfoDetails');
        $this->appendText($xml, $otherInfo, 'Narration1', $order->order_note ?? '');
        $sale->appendChild($otherInfo);
    }

    /**
     * Build ItemEntries.
     */
    private function buildItemEntries(DOMDocument $xml, \DOMElement $sale, object $order): void
    {
        $itemEntries = $xml->createElement('ItemEntries');
        foreach ($order->details->values() as $index => $detail) {
            $item = $xml->createElement('ItemDetail');
            $product = $detail->product;
            $unit = $detail->unit;
            $tax = $detail->tax;
            /*Date */
            $this->appendText($xml, $item, 'Date', Carbon::parse($order->order_date)->format('d-m-Y'));
            /*Voucher Type */
            $this->appendText($xml, $item, 'VchType', '9');
            /*Voucher Number */
            $this->appendText($xml, $item, 'VchNo', $order->order_no);
            /*Serial Number */
            $this->appendText($xml, $item, 'SrNo', (string) ($index + 1));
            /*BUSY Product */
            $this->appendText($xml, $item, 'ItemName', $this->getProductBusyName($product));
            /*Unit*/
            if ($unit) {
                $this->appendText($xml, $item, 'UnitName', $this->getUnitBusyName($unit));
                $this->appendText($xml, $item, 'AltUnitName', $this->getUnitBusyName($unit));
            }

            /*Conversion Factor */
            $this->appendText($xml, $item, 'ConFactor', '1');
         
            /*Quantity */
            $this->appendText($xml, $item, 'Qty', $this->formatNumber($detail->quantity));
            $this->appendText($xml, $item, 'QtyMainUnit', $this->formatNumber($detail->quantity));
            $this->appendText($xml, $item, 'QtyAltUnit', $this->formatNumber($detail->quantity));

            /*Tax Category*/
            if ($tax) {
                $this->appendText($xml, $item, 'ItemTaxCategory', $this->getTaxBusyName($tax));
            }

            /*Price */
            $this->appendText($xml, $item, 'Price', $this->formatNumber($detail->rate));
            $this->appendText($xml, $item, 'PriceAltUnit', $this->formatNumber($detail->rate));

            /*Amount */
            $this->appendText($xml, $item, 'Amt', $this->formatNumber((float) $detail->quantity * (float) $detail->rate));
            $this->appendText($xml, $item, 'NettAmount', $this->formatNumber($detail->taxable_amount));

            /* Discount */
            $this->appendText($xml, $item, 'CompoundDiscount', $this->formatNumber($detail->discount_percent));

            /* Tax */
            $this->appendText($xml, $item, 'STAmount', $this->formatNumber($detail->tax_amount));
            $this->appendText($xml, $item, 'STPercent', $this->formatNumber($detail->tax_rate));

            /* Description */
            if ($detail->description) {
                $this->appendText($xml, $item, 'ItemDescInfo', $detail->description);
            }

            /* Empty BUSY structures */
            $item->appendChild($xml->createElement('ItemSerialNoEntries'));
            $item->appendChild($xml->createElement('ParamStockEntries'));
            $item->appendChild($xml->createElement('BatchEntries'));
            $itemEntries->appendChild($item);
        }

        $sale->appendChild($itemEntries);
    }

    /* Build BillSundries. Currently used for order-level discount. */
    private function buildBillSundries(DOMDocument $xml, \DOMElement $sale, object $order): void {

        if ((float) $order->busy_discount_amount <= 0) {
            return;
        }

        $billSundries = $xml->createElement('BillSundries');
        $detail = $xml->createElement('BSDetail');

        $this->appendText($xml, $detail, 'SrNo', '1');
        $this->appendText($xml, $detail, 'BSName', 'Discount');
        $this->appendText($xml, $detail, 'Amt', $this->formatNumber($order->busy_discount_amount));
        $this->appendText($xml, $detail, 'Date', Carbon::parse($order->order_date)->format('d-m-Y'));
        $this->appendText($xml, $detail, 'VchNo', $order->order_no);
        $this->appendText($xml, $detail, 'VchType', '9');

        $billSundries->appendChild($detail);
        $sale->appendChild($billSundries);
    }

    /* Build PendingBillDetails. */
    private function buildPendingBillDetails(DOMDocument $xml, \DOMElement $sale, object $order): void {

        $pending = $xml->createElement('PendingBillDetails');
        $billDetail = $xml->createElement('BillDetail');

        $this->appendText($xml, $billDetail, 'MasterName1', $this->getClientBusyName($order));
        $refs = $xml->createElement('BillRefs');

        /* Method 1 = New Reference */
        $this->appendText($xml, $refs, 'Method', '1');
        $this->appendText($xml, $refs, 'SrNo', '1');
        $this->appendText($xml, $refs, 'RefNo', $order->order_no);
        $this->appendText($xml, $refs, 'Date', Carbon::parse($order->order_date)->format('d-m-Y'));
        $this->appendText($xml, $refs, 'DueDate', Carbon::parse($order->due_date ?: $order->order_date)->format('d-m-Y'));

        /* Positive value for receivable */
        $this->appendText($xml, $refs, 'Value1', $this->formatNumber($order->grand_total));
        $this->appendText($xml, $refs, 'VchType', '9');
        $billDetail->appendChild($refs);
        $pending->appendChild($billDetail);
        $sale->appendChild($pending);
    }

    /*Get BUSY customer name. The BUSY XML requires MasterName1. We use the DSA client's stored BUSY mapping/name. */
    private function getClientBusyName(object $order): string {
        $client = $order->client;

        /*If your parties_busy table has a dedicated BUSY name column, use it here. */
        return trim((string) (($client->company_name ?? '') ?: ($client->name ?? '') ?: $client->busycustomer_id));
    }

    /* Get BUSY product name. */
    private function getProductBusyName(object $product): string {
        return trim((string) ($product->product_name ?: $product->busyproduct_id));
    }

    /* Get BUSY unit name. */
    private function getUnitBusyName(object $unit): string {
        return trim((string) ($unit->name ?: $unit->symbol ?: $unit->busyunit_id));
    }

    /* Get BUSY tax name. */
    private function getTaxBusyName(object $tax): string {
        return trim((string) ($tax->name ?: $tax->display_name ?: $tax->busytax_id));
    }

    /* Add XML element. */
    private function appendText(DOMDocument $xml, \DOMElement $parent, string $name, string $value): void {

        $element = $xml->createElement($name);
        $element->appendChild($xml->createTextNode($value));
        $parent->appendChild($element);
    }

    /* Format numeric values for BUSY. */
    private function formatNumber($value): string {
        return number_format((float) $value, 2, '.', '');
    }

    /* Try to extract BUSY voucher ID from response. BUSY response structure can vary by installation/version, so this deliberately checks common formats. */
    private function extractBusyVoucherId(string $body): ?string {
        $body = trim($body);
        if ($body === '') {
            return null;
        }
        Log::info("Data or body received form the busy", [$body]);
        // BUSY may return the voucher ID as plain text
        if (ctype_digit($body)) {
            return $body;
        }
        /* XML response */
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        if ($xml !== false) {
            $possibleFields = [
                'VchCode',
                'VoucherCode',
                'VchId',
                'VoucherId',
                'Id',
            ];
            foreach ($possibleFields as $field) {
                if (isset($xml->{$field})) {
                    $value = trim((string) $xml->{$field});
                    if ($value !== '') {
                        return $value;
                    }
                }
            }
        }

        /* JSON response */
        $json = json_decode($body, true);
        if (is_array($json)) {
            foreach (['VchCode', 'VoucherCode', 'VchId', 'VoucherId', 'id'] as $key) {
                if (isset($json[$key]) && $json[$key] !== '') {
                    return (string) $json[$key];
                }
            }
        }

        /* Do not invent an ID. */
        return null;
    }
}
