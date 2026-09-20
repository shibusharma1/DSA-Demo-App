<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Services\busy\BusyToDsaOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;


class OrderController extends Controller
{

    public function index(Request $request)
    {
        $companyId = 1;
        $query = Order::query()->where('company_id', $companyId)->with('client')->withCount('details');

        /* Search */
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('order_no', 'like', "%{$search}%")
                    ->orWhere('busyorder_id', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($clientQuery) use ($search) {
                        $clientQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%");
                    });
            });
        }

        /* Order Status */
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        /*BUSY Sync Status */
        if ($request->filled('busy_sync_status')) {
            $query->where('busy_sync_status', $request->busy_sync_status);
        }

        /* Date Filter */
        if ($request->filled('from_date')) {
            $query->whereDate('order_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('order_date', '<=', $request->to_date);
        }

        /* Pagination */
        $orders = $query->latest('id')->paginate(20)->withQueryString();

        /*Summary */
        $summaryQuery = Order::query()->where('company_id', $companyId);
        $totalOrders = (clone $summaryQuery)->count();
        $syncedOrders = (clone $summaryQuery)->where('busy_sync_status', 'synced')->count();
        $pendingOrders = (clone $summaryQuery)
            ->where(function ($q) {
                $q->whereNull('busy_sync_status')
                    ->orWhere('busy_sync_status', 'pending');
            })->count();

        $failedOrders = (clone $summaryQuery)->where('busy_sync_status', 'failed')->count();
        return view('orders.index', compact(
            'orders',
            'totalOrders',
            'syncedOrders',
            'pendingOrders',
            'failedOrders'
        ));
    }
    /**
     * Display the create order page.
     */
    public function create(Request $request): View
    {
        $companyId = (int) ($request->user()?->company_id ?? 1);
        /*Clients */
        $clients = DB::table('parties_busy')
            ->where('company_id', $companyId)
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', '!=', 'Inactive');
            })
            ->orderByRaw('COALESCE(name, company_name)')
            ->get([
                'id',
                'name',
                'company_name',
                'mobile',
                'busyparty_id',
            ]);

        /*Products */
        $products = DB::table('products')
            ->leftJoin('unit_types', 'unit_types.id', '=', 'products.unit')
            ->where('products.company_id', $companyId)
            ->where(function ($query) {
                $query->whereNull('products.status')
                    ->orWhere('products.status', '!=', 'Inactive');
            })
            ->orderBy('products.product_name')
            ->get([
                'products.id',
                'products.product_name',
                'products.product_code',
                'products.mrp',
                'products.busyproduct_id',
                'products.unit',
                'unit_types.name as unit_name',
                'unit_types.symbol as unit_symbol',
            ]);

        /*Taxes */
        $taxes = DB::table('tax_types')
            ->where('company_id', $companyId)
            ->orderBy('percent')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'display_name',
                'percent',
                'busytax_id',
            ]);

        /*Units */
        $units = DB::table('unit_types')
            ->where('company_id', $companyId)
            ->where(function ($query) {
                $query->whereNull('status')->orWhere('status', '!=', 'Inactive');
            })
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'symbol',
                'busyunit_id',
            ]);

        return view('orders.create', compact(
            'companyId',
            'clients',
            'products',
            'taxes',
            'units'
        ));
    }

    /**
     * Store a new order.
     */
    public function store(Request $request): RedirectResponse
    {
        $companyId = (int) ($request->user()?->company_id ?? 1);
        $companyId = 1;

        $validated = $request->validate([
            'client_id' => [
                'required',
                'integer',
                'exists:parties_busy,id',
            ],

            'order_date' => [
                'required',
                'date',
            ],

            'order_to_id' => [
                'nullable',
                'integer',
            ],

            'order_notes' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'delivery_charge' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'products' => [
                'required',
                'array',
                'min:1',
            ],

            'products.*.product_id' => [
                'required',
                'integer',
                'exists:products,id',
            ],

            'products.*.unit_id' => [
                'nullable',
                'integer',
                'exists:unit_types,id',
            ],

            'products.*.tax_id' => [
                'nullable',
                'integer',
                'exists:tax_types,id',
            ],

            'products.*.quantity' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'products.*.rate' => [
                'required',
                'numeric',
                'min:0',
            ],

            'products.*.discount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'products.*.discount_type' => [
                'nullable',
                'in:percent,amount',
            ],
        ]);

        /*Security: ensure selected records belong to this company */
        $client = DB::table('parties_busy')
            ->where('id', $validated['client_id'])
            ->where('company_id', $companyId)
            ->first();

        if (!$client) {
            return back()
                ->withInput()
                ->withErrors([
                    'client_id' => 'The selected customer does not belong to this company.',
                ]);
        }

        $productIds = collect($validated['products'])
            ->pluck('product_id')
            ->unique()
            ->values();

        $validProducts = DB::table('products')
            ->where('company_id', $companyId)
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        if ($validProducts->count() !== $productIds->count()) {
            return back()
                ->withInput()
                ->withErrors([
                    'products' => 'One or more selected products are invalid.',
                ]);
        }

        $unitIds = collect($validated['products'])
            ->pluck('unit_id')
            ->filter()
            ->unique()
            ->values();

        $validUnits = DB::table('unit_types')
            ->where('company_id', $companyId)
            ->whereIn('id', $unitIds)
            ->pluck('id')
            ->flip();

        $taxIds = collect($validated['products'])
            ->pluck('tax_id')
            ->filter()
            ->unique()
            ->values();

        $validTaxes = DB::table('tax_types')
            ->where('company_id', $companyId)
            ->whereIn('id', $taxIds)
            ->get()
            ->keyBy('id');

        foreach ($validated['products'] as $index => $row) {

            if (!empty($row['unit_id']) && !$validUnits->has((int) $row['unit_id'])) {
                return back()
                    ->withInput()
                    ->withErrors([
                        "products.$index.unit_id" =>
                        'The selected unit is invalid.',
                    ]);
            }

            if (!empty($row['tax_id']) && !$validTaxes->has((int) $row['tax_id'])) {
                return back()
                    ->withInput()
                    ->withErrors([
                        "products.$index.tax_id" =>
                        'The selected tax is invalid.',
                    ]);
            }
        }
        try {
            $order = DB::transaction(function () use ($validated, $companyId, $validTaxes) {
                $subTotal = 0;
                $totalDiscount = 0;
                $totalTax = 0;
                $preparedDetails = [];
                /*Calculate every line */
                foreach ($validated['products'] as $index => $row) {
                    $quantity = (float) $row['quantity'];
                    $rate = (float) $row['rate'];
                    $grossAmount = round($quantity * $rate, 2);
                    $discountValue = (float) ($row['discount'] ?? 0);
                    $discountType = $row['discount_type'] ?? 'percent';
                    if ($discountType === 'percent') {
                        $discountAmount = round(($grossAmount * $discountValue) / 100, 2);
                    } else {
                        $discountAmount = round($discountValue, 2);
                    }
                    /*Never allow discount greater than line amount */

                    $discountAmount = min($discountAmount, $grossAmount);
                    $taxableAmount = round($grossAmount - $discountAmount, 2);
                    $taxRate = 0;
                    if (!empty($row['tax_id'])) {
                        $tax = $validTaxes->get((int) $row['tax_id']);
                        $taxRate = (float) ($tax?->percent ?? 0);
                    }

                    $taxAmount = round(($taxableAmount * $taxRate) / 100, 2);

                    $lineAmount = round($taxableAmount + $taxAmount, 2);

                    $appliedRate = $quantity > 0 ? round($taxableAmount / $quantity, 4) : 0;
                    $subTotal += $grossAmount;
                    $totalDiscount += $discountAmount;
                    $totalTax += $taxAmount;

                    $preparedDetails[] = [
                        'product_id' => (int) $row['product_id'],
                        'unit_id' => !empty($row['unit_id']) ? (int) $row['unit_id'] : null,
                        'tax_id' => !empty($row['tax_id']) ? (int) $row['tax_id'] : null,
                        'rate' => $rate,
                        'quantity' => $quantity,
                        'discount' => $discountValue,
                        'discount_type' => $discountType,
                        'discount_amount' => $discountAmount,
                        'applied_rate' => $appliedRate,
                        'tax_rate' => $taxRate,
                        'tax_amount' => $taxAmount,
                        'taxable_amount' => $taxableAmount,
                        'amount' => $lineAmount,
                        'description' => $row['description'] ?? null,
                        'sort_order' => $index,
                    ];
                }

                $subTotal = round($subTotal, 2);
                $totalDiscount = round($totalDiscount, 2);
                $totalTax = round($totalTax, 2);
                $deliveryCharge = round((float) ($validated['delivery_charge'] ?? 0), 2);

                $grandTotal = round(
                    $subTotal
                        - $totalDiscount
                        + $totalTax
                        + $deliveryCharge,
                    2
                );

                /*Generate internal order number*/
                $nextId = (int) (DB::table('orders')->max('id') ?? 0) + 1;
                $orderNo = 'ORD-' . str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
                /* Create Order */
                $order = Order::create([
                    'company_id' => $companyId,
                    'client_id' => $validated['client_id'],
                    /*BUSY ID is intentionally NULL here. - It will be populated after successful BUSY sync.*/
                    'busyorder_id' => null,
                    'order_no' => $orderNo,
                    'order_date' => $validated['order_date'],
                    'order_to_id' => $validated['order_to_id'] ?? null,
                    'order_notes' => $validated['order_notes'] ?? null,
                    'sub_total' => $subTotal,
                    'discount' => $totalDiscount,
                    'total_tax' => $totalTax,
                    'delivery_charge' => $deliveryCharge,
                    'grand_total' => $grandTotal,
                    'status' => 'Pending',
                    'busy_sync_status' => 'Pending',
                    'busy_sync_message' => null,
                    'busy_synced_at' => null,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Create Order Details
                |--------------------------------------------------------------------------
                */

                foreach ($preparedDetails as $detail) {

                    $order->details()->create(
                        $detail
                    );
                }

                return $order;
            });

            return redirect()
                ->route('orders.show', $order->id)
                ->with(
                    'success',
                    "Order {$order->order_no} created successfully."
                );
        } catch (Throwable $e) {

            report($e);

            return back()
                ->withInput()
                ->withErrors([
                    'order' =>
                    'Unable to create the order. Please try again.',
                ]);
        }
    }

    /**
     * Display order.
     */
    public function show(Order $order): View
    {
        $order->load([
            'client',
            'details.product',
            'details.unit',
            'details.tax',
        ]);

        return view(
            'orders.show',
            compact('order')
        );
    }

    /**
     * Product information endpoint.
     */
    public function product(
        Request $request,
        int $product
    ): JsonResponse {

        $companyId = (int) (
            $request->user()?->company_id ?? 1
        );

        $data = DB::table('products')
            ->leftJoin(
                'unit_types',
                'unit_types.id',
                '=',
                'products.unit'
            )
            ->where('products.company_id', $companyId)
            ->where('products.id', $product)
            ->first([
                'products.id',
                'products.product_name',
                'products.product_code',
                'products.mrp',
                'products.unit',
                'unit_types.name as unit_name',
                'unit_types.symbol as unit_symbol',
            ]);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'product' => $data,
        ]);
    }
    /**
     * Push an order to BUSY.
     */
    public function syncToBusy(
        Order $order,
        BusyToDsaOrder $busyToDsaOrder
    ): RedirectResponse {

        $result = $busyToDsaOrder->pushOrder(
            $order->id
        );

        if ($result['success']) {

            return redirect()
                ->route('orders.show', $order->id)
                ->with(
                    'success',
                    $result['message']
                );
        }

        return redirect()
            ->route('orders.show', $order->id)
            ->withErrors([
                'busy' => $result['error']
                    ?? 'Unable to synchronize order with BUSY.',
            ]);
    }
}
