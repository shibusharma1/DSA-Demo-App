<?php

namespace App\Services\busy;

use App\Models\Order;
use App\Services\BusyApiService;
use Carbon\Carbon;
use DOMDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class BusyToDsaOrder
{
    public function __construct(
        private readonly BusyApiService $busyApiService
    ) {}

    /**
     * Push a DSA order to BUSY as a Sale Voucher.
     */
    public function pushOrder(int $orderId): array
    {
        $result = [
            'success' => false,
            'order_id' => $orderId,
            'busyorder_id' => null,
            'message' => null,
            'error' => null,
        ];

        try {

            /*
            |--------------------------------------------------------------------------
            | Load complete order
            |--------------------------------------------------------------------------
            */

            $order = Order::with([
                'client',
                'details.product',
                'details.unit',
                'details.tax',
            ])->find($orderId);

            if (!$order) {
                throw new \RuntimeException(
                    'Order not found.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Basic validation
            |--------------------------------------------------------------------------
            */

            if (!$order->client) {
                throw new \RuntimeException(
                    'Order customer is not available.'
                );
            }

            if (!$order->client->busyparty_id) {
                throw new \RuntimeException(
                    'Selected customer is not mapped to BUSY.'
                );
            }

            if ($order->details->isEmpty()) {
                throw new \RuntimeException(
                    'Order does not contain any products.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Validate every detail
            |--------------------------------------------------------------------------
            */

            foreach ($order->details as $detail) {

                if (!$detail->product) {
                    throw new \RuntimeException(
                        "Product missing for order detail #{$detail->id}."
                    );
                }

                if (!$detail->product->busyproduct_id) {
                    throw new \RuntimeException(
                        "Product {$detail->product->product_name} is not mapped to BUSY."
                    );
                }

                if (
                    $detail->unit_id &&
                    (!$detail->unit || !$detail->unit->busyunit_id)
                ) {
                    throw new \RuntimeException(
                        "Unit is not mapped to BUSY for product {$detail->product->product_name}."
                    );
                }

                if (
                    $detail->tax_id &&
                    (!$detail->tax || !$detail->tax->busytax_id)
                ) {
                    throw new \RuntimeException(
                        "Tax is not mapped to BUSY for product {$detail->product->product_name}."
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Generate XML
            |--------------------------------------------------------------------------
            */

            $xml = $this->buildSaleXml($order);

            Log::channel('busy')->info(
                'BUSY Sale XML Generated',
                [
                    'order_id' => $order->id,
                    'order_no' => $order->order_no,
                    'xml' => $xml,
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Send to BUSY
            |--------------------------------------------------------------------------
            */
            if ($order->busyorder_id) {

                // Existing BUSY voucher → MODIFY
                $response = $this->busyApiService
                    ->modifySaleVoucher($xml);
            } else {

                // New order → CREATE
                $response = $this->busyApiService
                    ->createSaleVoucher($xml);
            }
            if (!($response['success'] ?? false)) {

                throw new \RuntimeException(
                    $response['description']
                        ?? 'BUSY Sale Voucher creation failed.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Extract BUSY voucher identifier
            |--------------------------------------------------------------------------
            */

            $busyOrderId = $this->extractBusyVoucherId(
                $response['body'] ?? ''
            );
            if (!$busyOrderId) {
                throw new \RuntimeException(
                    'BUSY returned HTTP 200 but did not return a voucher ID or confirmation. busy ID not received'
                );
            }
            /*
            |--------------------------------------------------------------------------
            | Update DSA Order
            |--------------------------------------------------------------------------
            */

            $order->update([
                'busyorder_id' => $busyOrderId,
                'busy_sync_status' => 'Synced',
                'busy_sync_message' => null,
                'busy_synced_at' => now(),
                'status' => 'Confirmed',
            ]);

            $result['success'] = true;
            $result['busyorder_id'] = $busyOrderId;
            $result['message'] =
                'Order successfully synchronized with BUSY.';

            Log::channel('busy')->info(
                'BUSY Sale Sync Completed',
                [
                    'order_id' => $order->id,
                    'order_no' => $order->order_no,
                    'busyorder_id' => $busyOrderId,
                ]
            );

            return $result;
        } catch (Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Mark order as failed
            |--------------------------------------------------------------------------
            */

            Order::where('id', $orderId)->update([
                'busy_sync_status' => 'Failed',
                'busy_sync_message' => $e->getMessage(),
            ]);

            $result['error'] = $e->getMessage();

            Log::channel('busy')->error(
                'BUSY Sale Sync Failed',
                [
                    'order_id' => $orderId,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );

            return $result;
        }
    }

    /**
     * Build BUSY Sale XML.
     */
    private function buildSaleXml(Order $order): string
    {
        $xml = new DOMDocument(
            '1.0',
            'UTF-8'
        );

        $xml->formatOutput = false;

        /*
        |--------------------------------------------------------------------------
        | Root
        |--------------------------------------------------------------------------
        */

        $sale = $xml->createElement('Sale');

        $xml->appendChild($sale);

        /*
        |--------------------------------------------------------------------------
        | Voucher Series
        |--------------------------------------------------------------------------
        */

        $this->appendText(
            $xml,
            $sale,
            'VchSeriesName',
            'Main'
        );

        /*
        |--------------------------------------------------------------------------
        | Date
        |--------------------------------------------------------------------------
        */

        $this->appendText(
            $xml,
            $sale,
            'Date',
            Carbon::parse(
                $order->order_date
            )->format('d-m-Y')
        );

        /*
        |--------------------------------------------------------------------------
        | Voucher Type
        |--------------------------------------------------------------------------
        */

        $this->appendText(
            $xml,
            $sale,
            'VchType',
            '9'
        );

        /*
        |--------------------------------------------------------------------------
        | Stock Updation Date
        |--------------------------------------------------------------------------
        */

        $this->appendText(
            $xml,
            $sale,
            'StockUpdationDate',
            Carbon::parse(
                $order->order_date
            )->format('d-m-Y')
        );

        /*
        |--------------------------------------------------------------------------
        | Voucher Number
        |--------------------------------------------------------------------------
        */

        $this->appendText(
            $xml,
            $sale,
            'VchNo',
            $order->order_no
        );

        /*
|--------------------------------------------------------------------------
| Sales Type
|--------------------------------------------------------------------------
*/

        $this->appendText(
            $xml,
            $sale,
            'STPTName',
            'Local-ItemWise'
        );


        /*
        |--------------------------------------------------------------------------
        | Customer
        |--------------------------------------------------------------------------
        */

        $this->appendText(
            $xml,
            $sale,
            'MasterName1',
            $this->getClientBusyName($order)
        );

        /*
        |--------------------------------------------------------------------------
        | Transaction Currency
        |--------------------------------------------------------------------------
        */

        $this->appendText(
            $xml,
            $sale,
            'TranCurName',
            'Rs.'
        );

        /*
        |--------------------------------------------------------------------------
        | Input Type
        |--------------------------------------------------------------------------
        */

        $this->appendText(
            $xml,
            $sale,
            'InputType',
            '1'
        );

        /*
        |--------------------------------------------------------------------------
        | Billing Details
        |--------------------------------------------------------------------------
        */

        $this->buildBillingDetails(
            $xml,
            $sale,
            $order
        );

        /*
        |--------------------------------------------------------------------------
        | Other Information
        |--------------------------------------------------------------------------
        */

        $this->buildOtherInformation(
            $xml,
            $sale,
            $order
        );

        /*
        |--------------------------------------------------------------------------
        | Item Entries
        |--------------------------------------------------------------------------
        */

        $this->buildItemEntries(
            $xml,
            $sale,
            $order
        );

        /*
        |--------------------------------------------------------------------------
        | Bill Sundries
        |--------------------------------------------------------------------------
        */

        $this->buildBillSundries(
            $xml,
            $sale,
            $order
        );

        /*
        |--------------------------------------------------------------------------
        | Pending Bill Details
        |--------------------------------------------------------------------------
        */

        $this->buildPendingBillDetails(
            $xml,
            $sale,
            $order
        );

        return $xml->saveXML(
            $xml->documentElement
        );
    }

    /**
     * Build BillingDetails.
     */
    private function buildBillingDetails(
        DOMDocument $xml,
        \DOMElement $sale,
        Order $order
    ): void {

        $billing = $xml->createElement(
            'BillingDetails'
        );

        $this->appendText(
            $xml,
            $billing,
            'PartyName',
            $this->getClientBusyName($order)
        );

        $client = $order->client;

        $this->appendText(
            $xml,
            $billing,
            'MobileNo',
            $client->mobile ?? ''
        );

        $sale->appendChild($billing);
    }

    /**
     * Build VchOtherInfoDetails.
     */
    private function buildOtherInformation(
        DOMDocument $xml,
        \DOMElement $sale,
        Order $order
    ): void {

        $otherInfo = $xml->createElement(
            'VchOtherInfoDetails'
        );

        $this->appendText(
            $xml,
            $otherInfo,
            'Narration1',
            $order->order_notes ?? ''
        );

        $sale->appendChild(
            $otherInfo
        );
    }

    /**
     * Build ItemEntries.
     */
    private function buildItemEntries(
        DOMDocument $xml,
        \DOMElement $sale,
        Order $order
    ): void {

        $itemEntries = $xml->createElement(
            'ItemEntries'
        );

        foreach (
            $order->details
                ->sortBy('sort_order')
            as $index => $detail
        ) {

            $item = $xml->createElement(
                'ItemDetail'
            );

            $product = $detail->product;
            $unit = $detail->unit;
            $tax = $detail->tax;

            /*
            |--------------------------------------------------------------------------
            | Date
            |--------------------------------------------------------------------------
            */

            $this->appendText(
                $xml,
                $item,
                'Date',
                Carbon::parse(
                    $order->order_date
                )->format('d-m-Y')
            );

            /*
            |--------------------------------------------------------------------------
            | Voucher Type
            |--------------------------------------------------------------------------
            */

            $this->appendText(
                $xml,
                $item,
                'VchType',
                '9'
            );

            /*
            |--------------------------------------------------------------------------
            | Voucher Number
            |--------------------------------------------------------------------------
            */

            $this->appendText(
                $xml,
                $item,
                'VchNo',
                $order->order_no
            );

            /*
            |--------------------------------------------------------------------------
            | Serial Number
            |--------------------------------------------------------------------------
            */

            $this->appendText(
                $xml,
                $item,
                'SrNo',
                (string) ($index + 1)
            );

            /*
            |--------------------------------------------------------------------------
            | BUSY Product
            |--------------------------------------------------------------------------
            */

            $this->appendText(
                $xml,
                $item,
                'ItemName',
                $this->getProductBusyName(
                    $product
                )
            );

            /*
            |--------------------------------------------------------------------------
            | Unit
            |--------------------------------------------------------------------------
            */

            if ($unit) {

                $this->appendText(
                    $xml,
                    $item,
                    'UnitName',
                    $this->getUnitBusyName(
                        $unit
                    )
                );

                $this->appendText(
                    $xml,
                    $item,
                    'AltUnitName',
                    $this->getUnitBusyName(
                        $unit
                    )
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Conversion Factor
            |--------------------------------------------------------------------------
            */

            $this->appendText(
                $xml,
                $item,
                'ConFactor',
                '1'
            );

            /*
            |--------------------------------------------------------------------------
            | Quantity
            |--------------------------------------------------------------------------
            */

            $this->appendText(
                $xml,
                $item,
                'Qty',
                $this->formatNumber(
                    $detail->quantity
                )
            );

            $this->appendText(
                $xml,
                $item,
                'QtyMainUnit',
                $this->formatNumber(
                    $detail->quantity
                )
            );

            $this->appendText(
                $xml,
                $item,
                'QtyAltUnit',
                $this->formatNumber(
                    $detail->quantity
                )
            );

            /*
            |--------------------------------------------------------------------------
            | Tax Category
            |--------------------------------------------------------------------------
            */

            if ($tax) {

                $this->appendText(
                    $xml,
                    $item,
                    'ItemTaxCategory',
                    $this->getTaxBusyName(
                        $tax
                    )
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Price
            |--------------------------------------------------------------------------
            */

            $this->appendText(
                $xml,
                $item,
                'Price',
                $this->formatNumber(
                    $detail->rate
                )
            );

            $this->appendText(
                $xml,
                $item,
                'PriceAltUnit',
                $this->formatNumber(
                    $detail->rate
                )
            );

            /*
            |--------------------------------------------------------------------------
            | Amount
            |--------------------------------------------------------------------------
            */

            $this->appendText(
                $xml,
                $item,
                'Amt',
                $this->formatNumber(
                  (float) $detail->quantity * (float) $detail->rate
                )
            );

            $this->appendText(
                $xml,
                $item,
                'NettAmount',
                $this->formatNumber(
                    $detail->taxable_amount
                )
            );

            /*
            |--------------------------------------------------------------------------
            | Discount
            |--------------------------------------------------------------------------
            */

            $discountPercent = 0;

            if (
                $detail->discount_type === 'percent'
            ) {
                $discountPercent =
                    (float) $detail->discount;
            }

            $this->appendText(
                $xml,
                $item,
                'CompoundDiscount',
                $this->formatNumber(
                    $discountPercent
                )
            );

            /*
            |--------------------------------------------------------------------------
            | Tax
            |--------------------------------------------------------------------------
            */

            $this->appendText(
                $xml,
                $item,
                'STAmount',
                $this->formatNumber(
                    $detail->tax_amount
                )
            );

            $this->appendText(
                $xml,
                $item,
                'STPercent',
                $this->formatNumber(
                    $detail->tax_rate
                )
            );

            /*
            |--------------------------------------------------------------------------
            | Description
            |--------------------------------------------------------------------------
            */

            if ($detail->description) {

                $this->appendText(
                    $xml,
                    $item,
                    'ItemDescInfo',
                    $detail->description
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Empty BUSY structures
            |--------------------------------------------------------------------------
            */

            $item->appendChild(
                $xml->createElement(
                    'ItemSerialNoEntries'
                )
            );

            $item->appendChild(
                $xml->createElement(
                    'ParamStockEntries'
                )
            );

            $item->appendChild(
                $xml->createElement(
                    'BatchEntries'
                )
            );

            $itemEntries->appendChild(
                $item
            );
        }

        $sale->appendChild(
            $itemEntries
        );
    }

    /**
     * Build BillSundries.
     *
     * Currently used for order-level discount.
     */
    private function buildBillSundries(
        DOMDocument $xml,
        \DOMElement $sale,
        Order $order
    ): void {

        if ((float) $order->discount <= 0) {
            return;
        }

        $billSundries = $xml->createElement(
            'BillSundries'
        );

        $detail = $xml->createElement(
            'BSDetail'
        );

        $this->appendText(
            $xml,
            $detail,
            'SrNo',
            '1'
        );

        $this->appendText(
            $xml,
            $detail,
            'BSName',
            'Discount'
        );

        $this->appendText(
            $xml,
            $detail,
            'Amt',
            $this->formatNumber(
                $order->discount
            )
        );

        $this->appendText(
            $xml,
            $detail,
            'Date',
            Carbon::parse(
                $order->order_date
            )->format('d-m-Y')
        );

        $this->appendText(
            $xml,
            $detail,
            'VchNo',
            $order->order_no
        );



        $this->appendText(
            $xml,
            $detail,
            'VchType',
            '9'
        );

        $billSundries->appendChild(
            $detail
        );

        $sale->appendChild(
            $billSundries
        );
    }

    /**
     * Build PendingBillDetails.
     */
    private function buildPendingBillDetails(
        DOMDocument $xml,
        \DOMElement $sale,
        Order $order
    ): void {

        $pending = $xml->createElement(
            'PendingBillDetails'
        );

        $billDetail = $xml->createElement(
            'BillDetail'
        );

        $this->appendText(
            $xml,
            $billDetail,
            'MasterName1',
            $this->getClientBusyName($order)
        );

        $refs = $xml->createElement(
            'BillRefs'
        );

        /*
        |--------------------------------------------------------------------------
        | Method 1 = New Reference
        |--------------------------------------------------------------------------
        */

        $this->appendText(
            $xml,
            $refs,
            'Method',
            '1'
        );

        $this->appendText(
            $xml,
            $refs,
            'SrNo',
            '1'
        );

        $this->appendText(
            $xml,
            $refs,
            'RefNo',
            $order->order_no
        );

        $this->appendText(
            $xml,
            $refs,
            'Date',
            Carbon::parse(
                $order->order_date
            )->format('d-m-Y')
        );

        $this->appendText(
            $xml,
            $refs,
            'DueDate',
            Carbon::parse(
                $order->order_date
            )->format('d-m-Y')
        );

        /*
        |--------------------------------------------------------------------------
        | Positive value for receivable
        |--------------------------------------------------------------------------
        */

        $this->appendText(
            $xml,
            $refs,
            'Value1',
            $this->formatNumber(
                $order->grand_total
            )
        );

        $this->appendText(
            $xml,
            $refs,
            'VchType',
            '9'
        );

        $billDetail->appendChild(
            $refs
        );

        $pending->appendChild(
            $billDetail
        );

        $sale->appendChild(
            $pending
        );
    }

    /**
     * Get BUSY customer name.
     *
     * The BUSY XML requires MasterName1.
     *
     * We use the DSA client's stored BUSY mapping/name.
     */
    private function getClientBusyName(
        Order $order
    ): string {

        $client = $order->client;

        /*
        |--------------------------------------------------------------------------
        | If your clients table has a dedicated BUSY name column,
        | use it here.
        |--------------------------------------------------------------------------
        */

        return trim(
            (string) (
                $client->company_name
                ?: $client->name
                ?: $client->busyparty_id
            )
        );
    }

    /**
     * Get BUSY product name.
     */
    private function getProductBusyName(
        object $product
    ): string {

        return trim(
            (string) (
                $product->product_name
                ?: $product->busyproduct_id
            )
        );
    }

    /**
     * Get BUSY unit name.
     */
    private function getUnitBusyName(
        object $unit
    ): string {

        return trim(
            (string) (
                $unit->name
                ?: $unit->symbol
                ?: $unit->busyunit_id
            )
        );
    }

    /**
     * Get BUSY tax name.
     */
    private function getTaxBusyName(
        object $tax
    ): string {

        return trim(
            (string) (
                $tax->name
                ?: $tax->display_name
                ?: $tax->busytax_id
            )
        );
    }

    /**
     * Add XML element.
     */
    private function appendText(
        DOMDocument $xml,
        \DOMElement $parent,
        string $name,
        string $value
    ): void {

        $element = $xml->createElement(
            $name
        );

        $element->appendChild(
            $xml->createTextNode($value)
        );

        $parent->appendChild(
            $element
        );
    }

    /**
     * Format numeric values for BUSY.
     */
    private function formatNumber(
        $value
    ): string {

        return number_format(
            (float) $value,
            2,
            '.',
            ''
        );
    }

    /**
     * Try to extract BUSY voucher ID from response.
     *
     * BUSY response structure can vary by installation/version,
     * so this deliberately checks common formats.
     */
    private function extractBusyVoucherId(
        string $body
    ): ?string {

        $body = trim($body);

        if ($body === '') {
            return null;
        }
        Log::info("Data or body received form the busy", [$body]);
        // BUSY may return the voucher ID as plain text
        if (ctype_digit($body)) {
            return $body;
        }
        /*
        |--------------------------------------------------------------------------
        | XML response
        |--------------------------------------------------------------------------
        */

        libxml_use_internal_errors(true);

        $xml = simplexml_load_string(
            $body
        );

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

                    $value = trim(
                        (string) $xml->{$field}
                    );

                    if ($value !== '') {
                        return $value;
                    }
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | JSON response
        |--------------------------------------------------------------------------
        */

        $json = json_decode(
            $body,
            true
        );

        if (is_array($json)) {

            foreach (
                [
                    'VchCode',
                    'VoucherCode',
                    'VchId',
                    'VoucherId',
                    'id',
                ] as $key
            ) {

                if (
                    isset($json[$key]) &&
                    $json[$key] !== ''
                ) {
                    return (string) $json[$key];
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Do not invent an ID.
        |--------------------------------------------------------------------------
        */

        return null;
    }
}
