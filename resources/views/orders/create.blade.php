@extends('layout.app')

@section('content')

    <div class="min-h-screen bg-slate-100 py-6">

        <div class="mx-auto max-w-[1600px] px-4 sm:px-6 lg:px-8">

            {{-- =========================================================
             PAGE HEADER
        ========================================================== --}}

            <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-800">
                        Create Order
                    </h1>

                    <p class="mt-1 text-sm text-slate-500">
                        Create a new customer order and prepare it for BUSY synchronization.
                    </p>
                </div>

                <a href="{{ url()->previous() }}"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>

                    Back
                </a>

            </div>


            {{-- =========================================================
             VALIDATION ERRORS
        ========================================================== --}}

            @if ($errors->any())
                <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4">

                    <div class="flex gap-3">

                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-red-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v3.5m0 3.5h.01M10.29 3.86l-7.82 13.5A2 2 0 004.2 20.4h15.6a2 2 0 001.73-3.04l-7.82-13.5a2 2 0 00-3.42 0z" />
                        </svg>

                        <div>
                            <p class="font-semibold text-red-800">
                                Please correct the following:
                            </p>

                            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>

                    </div>

                </div>
            @endif


            {{-- =========================================================
             SUCCESS
        ========================================================== --}}

            @if (session('success'))
                <div
                    class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif


            <form id="orderForm" method="POST" action="{{ route('orders.store') }}">

                @csrf

                {{-- =====================================================
                 MAIN CARD
            ====================================================== --}}

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                    {{-- =================================================
                     ORDER INFORMATION
                ================================================== --}}

                    <div class="border-b border-slate-200 p-5 sm:p-6">

                        <div class="mb-5">
                            <h2 class="text-base font-semibold text-slate-800">
                                Order Information
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Select the customer and enter the basic order information.
                            </p>
                        </div>


                        <div class="grid grid-cols-1 gap-5 md:grid-cols-3">

                            {{-- Customer --}}

                            <div>

                                <label for="client_id" class="mb-2 block text-sm font-semibold text-slate-700">
                                    Party Name
                                    <span class="text-red-500">*</span>
                                </label>

                                <select id="client_id" name="client_id" required
                                    class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-3 text-sm text-slate-800 shadow-sm outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-100">

                                    <option value="">
                                        Select Party Name
                                    </option>

                                    @foreach ($clients as $client)
                                        <option value="{{ $client->id }}" @selected(old('client_id') == $client->id)>
                                            {{ $client->company_name ?: $client->name }}

                                            @if ($client->busyparty_id)
                                                — BUSY #{{ $client->busyparty_id }}
                                            @endif
                                        </option>
                                    @endforeach

                                </select>

                            </div>


                            {{-- Order To --}}

                            <div>

                                <label for="order_to_id" class="mb-2 block text-sm font-semibold text-slate-700">
                                    Order To
                                </label>

                                <select id="order_to_id" name="order_to_id"
                                    class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-3 text-sm text-slate-800 shadow-sm outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-100">

                                    <option value="">
                                        Select Order To
                                    </option>

                                    {{-- 
                                    Keep this empty until the existing
                                    Order To master/table is confirmed.
                                --}}

                                </select>

                                <p class="mt-1.5 text-xs text-slate-400">
                                    Optional
                                </p>

                            </div>


                            {{-- Date --}}

                            <div>

                                <label for="order_date" class="mb-2 block text-sm font-semibold text-slate-700">
                                    Order Date
                                    <span class="text-red-500">*</span>
                                </label>

                                <div class="relative">

                                    <input type="date" id="order_date" name="order_date"
                                        value="{{ old('order_date', now()->format('Y-m-d')) }}" required
                                        class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-3 text-sm text-slate-800 shadow-sm outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-100">

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- =================================================
                     PRODUCT TABLE
                ================================================== --}}

                    <div class="p-5 sm:p-6">

                        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                            <div>

                                <h2 class="text-base font-semibold text-slate-800">
                                    Products
                                    <span class="text-red-500">*</span>
                                </h2>

                                <p class="mt-1 text-sm text-slate-500">
                                    Add one or more products to this order.
                                </p>

                            </div>

                            <button type="button" id="addProductBtn"
                                class="inline-flex items-center justify-center gap-2 rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-300">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>

                                Add Product
                            </button>

                        </div>


                        {{-- =================================================
                         RESPONSIVE TABLE
                    ================================================== --}}

                        <div class="overflow-hidden rounded-xl border border-slate-200">

                            <div class="overflow-x-auto">

                                <table class="min-w-[1450px] w-full">

                                    <thead class="bg-teal-600 text-white">

                                        <tr>

                                            <th class="w-[270px] px-3 py-3 text-left text-sm font-semibold">
                                                Product
                                            </th>

                                            <th class="w-[150px] px-3 py-3 text-left text-sm font-semibold">
                                                Unit
                                            </th>

                                            <th class="w-[130px] px-3 py-3 text-left text-sm font-semibold">
                                                Rate
                                            </th>

                                            <th class="w-[110px] px-3 py-3 text-left text-sm font-semibold">
                                                Qty.
                                            </th>

                                            <th class="w-[250px] px-3 py-3 text-left text-sm font-semibold">
                                                Discount
                                            </th>

                                            <th class="w-[140px] px-3 py-3 text-left text-sm font-semibold">
                                                Applied Rate
                                            </th>

                                            <th class="w-[200px] px-3 py-3 text-left text-sm font-semibold">
                                                Tax
                                            </th>

                                            <th class="w-[150px] px-3 py-3 text-left text-sm font-semibold">
                                                Tax Amount
                                            </th>

                                            <th class="w-[160px] px-3 py-3 text-right text-sm font-semibold">
                                                Amount
                                            </th>

                                            <th class="w-[60px]"></th>

                                        </tr>

                                    </thead>


                                    <tbody id="productRows" class="divide-y divide-slate-200 bg-white">

                                    </tbody>

                                </table>

                            </div>


                            {{-- Empty State --}}

                            <div id="emptyProducts"
                                class="flex min-h-[180px] flex-col items-center justify-center bg-slate-50 px-6 text-center">

                                <div class="mb-3 rounded-full bg-teal-50 p-3">

                                    <svg class="h-6 w-6 text-teal-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 7l-8-4-8 4m16 0v10l-8 4-8-4V7m16 0l-8 4m-8-4l8 4m0 0v10" />
                                    </svg>

                                </div>

                                <p class="text-sm font-semibold text-slate-700">
                                    No products added
                                </p>

                                <p class="mt-1 text-sm text-slate-500">
                                    Click "Add Product" to start building this order.
                                </p>

                            </div>

                        </div>

                    </div>


                    {{-- =================================================
                     LOWER SECTION
                ================================================== --}}

                    <div class="border-t border-slate-200 p-5 sm:p-6">

                        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">

                            {{-- =================================================
                             NOTES
                        ================================================== --}}

                            <div>

                                <label for="order_notes" class="mb-2 block text-sm font-semibold text-slate-700">
                                    Order Notes
                                </label>

                                <textarea id="order_notes" name="order_notes" rows="8"
                                    placeholder="Write any notes or special instructions..."
                                    class="block w-full resize-y rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-100">{{ old('order_notes') }}</textarea>

                            </div>


                            {{-- =================================================
                             SUMMARY
                        ================================================== --}}

                            <div>

                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">

                                    <h2 class="mb-5 text-base font-semibold text-slate-800">
                                        Order Summary
                                    </h2>


                                    {{-- Subtotal --}}

                                    <div class="flex items-center justify-between py-2.5">

                                        <span class="text-sm text-slate-600">
                                            Sub Total
                                        </span>

                                        <span id="summarySubtotal" class="font-medium text-slate-800">
                                            Rs. 0.00
                                        </span>

                                    </div>


                                    {{-- Discount --}}

                                    <div class="flex items-center justify-between py-2.5">

                                        <span class="text-sm text-slate-600">
                                            Discount
                                        </span>

                                        <span id="summaryDiscount" class="font-medium text-slate-800">
                                            Rs. 0.00
                                        </span>

                                    </div>


                                    {{-- Tax --}}

                                    <div class="flex items-center justify-between py-2.5">

                                        <span class="text-sm text-slate-600">
                                            Taxes Implied
                                        </span>

                                        <span id="summaryTax" class="font-medium text-slate-800">
                                            Rs. 0.00
                                        </span>

                                    </div>


                                    {{-- Delivery --}}

                                    <div class="border-t border-slate-200 pt-4">

                                        <label for="delivery_charge"
                                            class="mb-2 block text-sm font-semibold text-slate-700">
                                            Delivery Charge
                                        </label>

                                        <div class="flex">

                                            <span
                                                class="inline-flex items-center rounded-l-lg border border-r-0 border-slate-300 bg-white px-3 text-sm text-slate-500">
                                                Rs
                                            </span>

                                            <input type="number" step="0.01" min="0" id="delivery_charge"
                                                name="delivery_charge" value="{{ old('delivery_charge', 0) }}"
                                                class="block w-full rounded-r-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100">

                                        </div>

                                    </div>


                                    {{-- Grand Total --}}

                                    <div class="mt-5 rounded-xl bg-slate-900 p-4">

                                        <div class="flex items-center justify-between">

                                            <span class="text-sm font-medium text-slate-300">
                                                Grand Total
                                            </span>

                                            <span id="summaryGrandTotal" class="text-2xl font-bold text-white">
                                                Rs. 0.00
                                            </span>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- =================================================
                     FOOTER ACTIONS
                ================================================== --}}

                    <div
                        class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 px-5 py-5 sm:flex-row sm:items-center sm:justify-end sm:px-6">

                        <a href="{{ url()->previous() }}"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                            Cancel
                        </a>

                        <button type="submit" id="createOrderBtn"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-teal-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-300">

                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>

                            Create Order

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>


    {{-- =============================================================
     PRODUCT ROW TEMPLATE
============================================================== --}}

    <template id="productRowTemplate">

        <tr class="product-row align-top">

            {{-- Product --}}

            <td class="p-2">

                <select name="products[INDEX][product_id]"
                    class="product-select block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100"
                    required>

                    <option value="">
                        Select Product
                    </option>

                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" data-rate="{{ $product->mrp ?? 0 }}"
                            data-unit-id="{{ $product->unit ?? '' }}" data-unit-name="{{ $product->unit_name ?? '' }}"
                            data-unit-symbol="{{ $product->unit_symbol ?? '' }}">
                            {{ $product->product_name }}
                            @if ($product->product_code)
                                — {{ $product->product_code }}
                            @endif
                        </option>
                    @endforeach

                </select>

            </td>


            {{-- Unit --}}

            <td class="p-2">

                <select name="products[INDEX][unit_id]"
                    class="unit-select block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100">

                    <option value="">
                        Select Unit
                    </option>

                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}">
                            {{ $unit->name }}
                            @if ($unit->symbol)
                                ({{ $unit->symbol }})
                            @endif
                        </option>
                    @endforeach

                </select>

            </td>


            {{-- Rate --}}

            <td class="p-2">

                <div class="relative">

                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">
                        Rs
                    </span>

                    <input type="number" name="products[INDEX][rate]" step="0.0001" min="0" value="0"
                        class="rate-input block w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-9 pr-3 text-sm outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100"
                        required>

                </div>

            </td>


            {{-- Quantity --}}

            <td class="p-2">

                <input type="number" name="products[INDEX][quantity]" step="0.0001" min="0.0001" value="1"
                    class="quantity-input block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100"
                    required>

            </td>


            {{-- Discount --}}

            <td class="p-2">

                <div class="flex">

                    <input type="number" name="products[INDEX][discount]" step="0.01" min="0" value="0"
                        class="discount-input min-w-0 flex-1 rounded-l-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100">

                    <select name="products[INDEX][discount_type]"
                        class="discount-type rounded-r-lg border border-l-0 border-slate-300 bg-slate-50 px-2 py-2.5 text-sm outline-none focus:border-teal-500">
                        <option value="percent">%</option>
                        <option value="amount">Rs</option>
                    </select>

                </div>

            </td>


            {{-- Applied Rate --}}

            <td class="p-2">

                <input type="text" readonly value="0.00"
                    class="applied-rate block w-full rounded-lg border border-slate-200 bg-slate-100 px-3 py-2.5 text-sm font-medium text-slate-600">

            </td>


            {{-- Tax --}}

            <td class="p-2">

                <select name="products[INDEX][tax_id]"
                    class="tax-select block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100">

                    <option value="">
                        No Tax
                    </option>

                    @foreach ($taxes as $tax)
                        <option value="{{ $tax->id }}" data-percent="{{ $tax->percent }}">
                            {{ $tax->display_name ?: $tax->name }}
                            @if ($tax->percent !== null)
                                ({{ number_format((float) $tax->percent, 2) }}%)
                            @endif
                        </option>
                    @endforeach

                </select>

            </td>


            {{-- Tax Amount --}}

            <td class="p-2">

                <input type="text" readonly value="Rs. 0.00"
                    class="tax-amount block w-full rounded-lg border border-slate-200 bg-slate-100 px-3 py-2.5 text-sm font-medium text-slate-600">

            </td>


            {{-- Amount --}}

            <td class="p-2 text-right">

                <input type="text" readonly value="Rs. 0.00"
                    class="line-amount block w-full rounded-lg border border-slate-200 bg-slate-100 px-3 py-2.5 text-right text-sm font-semibold text-slate-800">

            </td>


            {{-- Delete --}}

            <td class="p-2 text-center">

                <button type="button"
                    class="remove-product mt-1 inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 transition hover:bg-red-50 hover:text-red-600"
                    title="Remove product">

                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 7h12M9 7V4h6v3m-8 0l.7 13h6.6L15 7M10 11v5m4-5v5" />
                    </svg>

                </button>

            </td>

        </tr>

    </template>


    {{-- =============================================================
     JAVASCRIPT
============================================================== --}}

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const productRows =
                document.getElementById('productRows');

            const emptyProducts =
                document.getElementById('emptyProducts');

            const addProductBtn =
                document.getElementById('addProductBtn');

            const template =
                document.getElementById('productRowTemplate');

            const orderForm =
                document.getElementById('orderForm');

            const deliveryInput =
                document.getElementById('delivery_charge');

            let rowIndex = 0;


            /*
            |--------------------------------------------------------------------------
            | Currency
            |--------------------------------------------------------------------------
            */

            function money(value) {

                return 'Rs. ' + Number(value || 0)
                    .toLocaleString('en-IN', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
            }


            /*
            |--------------------------------------------------------------------------
            | Number
            |--------------------------------------------------------------------------
            */

            function number(value) {

                const parsed = parseFloat(value);

                return Number.isFinite(parsed) ?
                    parsed :
                    0;
            }


            /*
            |--------------------------------------------------------------------------
            | Update Empty State
            |--------------------------------------------------------------------------
            */

            function updateEmptyState() {

                const rows =
                    productRows.querySelectorAll('.product-row');

                emptyProducts.classList.toggle(
                    'hidden',
                    rows.length > 0
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Add Product Row
            |--------------------------------------------------------------------------
            */

            function addProductRow() {

                const html =
                    template.innerHTML.replaceAll(
                        'INDEX',
                        rowIndex
                    );

                productRows.insertAdjacentHTML(
                    'beforeend',
                    html
                );

                rowIndex++;

                updateEmptyState();

                const row =
                    productRows.lastElementChild;

                attachRowEvents(row);

                recalculateSummary();
            }


            /*
            |--------------------------------------------------------------------------
            | Product Selected
            |--------------------------------------------------------------------------
            */

            function handleProductChange(row) {

                const productSelect =
                    row.querySelector('.product-select');

                const selected =
                    productSelect.options[
                        productSelect.selectedIndex
                    ];

                if (!selected || !selected.value) {
                    return;
                }

                const rate =
                    number(selected.dataset.rate);

                const unitId =
                    selected.dataset.unitId;

                const unitSelect =
                    row.querySelector('.unit-select');

                row.querySelector('.rate-input').value =
                    rate > 0 ? rate.toFixed(4) : '0';

                if (unitId) {
                    unitSelect.value = unitId;
                }

                recalculateRow(row);
            }


            /*
            |--------------------------------------------------------------------------
            | Recalculate Row
            |--------------------------------------------------------------------------
            */

            function recalculateRow(row) {

                const rate =
                    number(
                        row.querySelector('.rate-input').value
                    );

                const quantity =
                    number(
                        row.querySelector('.quantity-input').value
                    );

                const discount =
                    number(
                        row.querySelector('.discount-input').value
                    );

                const discountType =
                    row.querySelector('.discount-type').value;

                const gross =
                    rate * quantity;

                let discountAmount = 0;

                if (discountType === 'percent') {

                    discountAmount =
                        gross * discount / 100;

                } else {

                    discountAmount =
                        discount;
                }

                discountAmount =
                    Math.min(
                        Math.max(discountAmount, 0),
                        gross
                    );

                const taxable =
                    gross - discountAmount;


                /*
                |--------------------------------------------------------------------------
                | Tax
                |--------------------------------------------------------------------------
                */

                const taxSelect =
                    row.querySelector('.tax-select');

                const selectedTax =
                    taxSelect.options[
                        taxSelect.selectedIndex
                    ];

                const taxRate =
                    selectedTax ?
                    number(selectedTax.dataset.percent) :
                    0;

                const taxAmount =
                    taxable * taxRate / 100;

                const amount =
                    taxable + taxAmount;

                const appliedRate =
                    quantity > 0 ?
                    taxable / quantity :
                    0;


                /*
                |--------------------------------------------------------------------------
                | Update UI
                |--------------------------------------------------------------------------
                */

                row.querySelector('.applied-rate').value =
                    appliedRate.toFixed(4);

                row.querySelector('.tax-amount').value =
                    money(taxAmount);

                row.querySelector('.line-amount').value =
                    money(amount);


                /*
                |--------------------------------------------------------------------------
                | Store calculated values as data
                |--------------------------------------------------------------------------
                */

                row.dataset.gross =
                    gross.toFixed(2);

                row.dataset.discountAmount =
                    discountAmount.toFixed(2);

                row.dataset.taxable =
                    taxable.toFixed(2);

                row.dataset.taxAmount =
                    taxAmount.toFixed(2);

                row.dataset.amount =
                    amount.toFixed(2);

                row.dataset.appliedRate =
                    appliedRate.toFixed(4);

                recalculateSummary();
            }


            /*
            |--------------------------------------------------------------------------
            | Recalculate Summary
            |--------------------------------------------------------------------------
            */

            function recalculateSummary() {

                let subtotal = 0;
                let discount = 0;
                let tax = 0;

                document
                    .querySelectorAll('.product-row')
                    .forEach(row => {

                        subtotal +=
                            number(row.dataset.gross);

                        discount +=
                            number(row.dataset.discountAmount);

                        tax +=
                            number(row.dataset.taxAmount);
                    });


                const delivery =
                    number(deliveryInput.value);

                const grandTotal =
                    subtotal -
                    discount +
                    tax +
                    delivery;


                document.getElementById(
                    'summarySubtotal'
                ).textContent = money(subtotal);

                document.getElementById(
                    'summaryDiscount'
                ).textContent = money(discount);

                document.getElementById(
                    'summaryTax'
                ).textContent = money(tax);

                document.getElementById(
                    'summaryGrandTotal'
                ).textContent = money(grandTotal);
            }


            /*
            |--------------------------------------------------------------------------
            | Attach Row Events
            |--------------------------------------------------------------------------
            */

            function attachRowEvents(row) {

                const productSelect =
                    row.querySelector('.product-select');

                const rateInput =
                    row.querySelector('.rate-input');

                const quantityInput =
                    row.querySelector('.quantity-input');

                const discountInput =
                    row.querySelector('.discount-input');

                const discountType =
                    row.querySelector('.discount-type');

                const taxSelect =
                    row.querySelector('.tax-select');

                const removeButton =
                    row.querySelector('.remove-product');


                productSelect.addEventListener(
                    'change',
                    () => handleProductChange(row)
                );

                rateInput.addEventListener(
                    'input',
                    () => recalculateRow(row)
                );

                quantityInput.addEventListener(
                    'input',
                    () => recalculateRow(row)
                );

                discountInput.addEventListener(
                    'input',
                    () => recalculateRow(row)
                );

                discountType.addEventListener(
                    'change',
                    () => recalculateRow(row)
                );

                taxSelect.addEventListener(
                    'change',
                    () => recalculateRow(row)
                );

                removeButton.addEventListener(
                    'click',
                    () => {

                        row.remove();

                        updateEmptyState();

                        recalculateSummary();
                    }
                );

                recalculateRow(row);
            }


            /*
            |--------------------------------------------------------------------------
            | Add Product
            |--------------------------------------------------------------------------
            */

            addProductBtn.addEventListener(
                'click',
                addProductRow
            );


            /*
            |--------------------------------------------------------------------------
            | Delivery Charge
            |--------------------------------------------------------------------------
            */

            deliveryInput.addEventListener(
                'input',
                recalculateSummary
            );


            /*
            |--------------------------------------------------------------------------
            | Form Submit Protection
            |--------------------------------------------------------------------------
            */

            orderForm.addEventListener(
                'submit',
                function(event) {

                    const rows =
                        productRows.querySelectorAll(
                            '.product-row'
                        );

                    if (rows.length === 0) {

                        event.preventDefault();

                        alert(
                            'Please add at least one product.'
                        );

                        return;
                    }

                    const client =
                        document.getElementById(
                            'client_id'
                        ).value;

                    if (!client) {

                        event.preventDefault();

                        alert(
                            'Please select a party.'
                        );

                        return;
                    }


                    const button =
                        document.getElementById(
                            'createOrderBtn'
                        );

                    button.disabled = true;

                    button.classList.add(
                        'opacity-70',
                        'cursor-not-allowed'
                    );

                    button.innerHTML = `
                <svg
                    class="h-4 w-4 animate-spin"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    ></circle>

                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                    ></path>
                </svg>

                Creating Order...
            `;
                }
            );


            /*
            |--------------------------------------------------------------------------
            | Initial State
            |--------------------------------------------------------------------------
            */

            updateEmptyState();

            addProductRow();

        });
    </script>

@endsection
