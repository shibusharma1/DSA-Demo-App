@extends('layout.app')

@section('content')

<div class="min-h-screen bg-slate-100 py-6">

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        {{-- Header --}}

        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <div class="flex items-center gap-3">

                    <h1 class="text-2xl font-bold text-slate-800">
                        {{ $order->order_no }}
                    </h1>

                    @php
                        $statusClasses = match($order->status) {
                            'Confirmed' => 'bg-emerald-100 text-emerald-700',
                            'Cancelled' => 'bg-red-100 text-red-700',
                            default => 'bg-amber-100 text-amber-700',
                        };
                    @endphp

                    <span
                        class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses }}"
                    >
                        {{ $order->status }}
                    </span>

                </div>

                <p class="mt-1 text-sm text-slate-500">
                    Order created on
                    {{ $order->order_date?->format('d M Y') }}
                </p>

            </div>


            <div class="flex flex-wrap gap-2">

                @if($order->busy_sync_status !== 'Synced')

                    <form
                        method="POST"
                        action="{{ route('orders.sync-busy', $order) }}"
                    >

                        @csrf

                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700"
                        >

                            <svg
                                class="h-4 w-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                />
                            </svg>

                            Sync to BUSY

                        </button>

                    </form>

                @endif

                <a
                    href="{{ route('orders.create') }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    New Order
                </a>

            </div>

        </div>


        {{-- Messages --}}

        @if(session('success'))

            <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                {{ session('success') }}
            </div>

        @endif


        @if($errors->any())

            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4">

                @foreach($errors->all() as $error)

                    <p class="text-sm text-red-700">
                        {{ $error }}
                    </p>

                @endforeach

            </div>

        @endif


        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- =====================================================
                 CUSTOMER
            ====================================================== --}}

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-1">

                <h2 class="mb-5 text-base font-semibold text-slate-800">
                    Customer
                </h2>

                <div>

                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                        Party
                    </p>

                    <p class="mt-1 font-semibold text-slate-800">
                        {{ $order->client->company_name ?: $order->client->name }}
                    </p>

                </div>


                @if($order->client->mobile)

                    <div class="mt-5">

                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            Mobile
                        </p>

                        <p class="mt-1 text-sm text-slate-700">
                            {{ $order->client->mobile }}
                        </p>

                    </div>

                @endif


                <div class="mt-5">

                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                        BUSY Party ID
                    </p>

                    <p class="mt-1 font-mono text-sm text-slate-700">
                        {{ $order->client->busyparty_id ?: 'Not mapped' }}
                    </p>

                </div>


                <div class="mt-5">

                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                        BUSY Sync
                    </p>

                    <div class="mt-2">

                        @if($order->busy_sync_status === 'Synced')

                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                Synced
                            </span>

                        @elseif($order->busy_sync_status === 'Failed')

                            <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                                Failed
                            </span>

                        @else

                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                                Pending
                            </span>

                        @endif

                    </div>

                </div>


                @if($order->busyorder_id)

                    <div class="mt-5">

                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            BUSY Voucher ID
                        </p>

                        <p class="mt-1 font-mono text-sm font-semibold text-slate-800">
                            {{ $order->busyorder_id }}
                        </p>

                    </div>

                @endif

            </div>


            {{-- =====================================================
                 ORDER SUMMARY
            ====================================================== --}}

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">

                <div class="mb-5 flex items-center justify-between">

                    <h2 class="text-base font-semibold text-slate-800">
                        Order Summary
                    </h2>

                    <span class="text-sm text-slate-500">
                        {{ $order->details->count() }}
                        {{ Str::plural('item', $order->details->count()) }}
                    </span>

                </div>


                <div class="overflow-x-auto">

                    <table class="w-full min-w-[800px]">

                        <thead>

                            <tr class="border-b border-slate-200 text-left">

                                <th class="pb-3 text-xs font-semibold uppercase tracking-wide text-slate-400">
                                    Product
                                </th>

                                <th class="pb-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-400">
                                    Qty
                                </th>

                                <th class="pb-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-400">
                                    Rate
                                </th>

                                <th class="pb-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-400">
                                    Tax
                                </th>

                                <th class="pb-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-400">
                                    Amount
                                </th>

                            </tr>

                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            @foreach($order->details as $detail)

                                <tr>

                                    <td class="py-4">

                                        <p class="font-medium text-slate-800">
                                            {{ $detail->product->product_name }}
                                        </p>

                                        @if($detail->unit)

                                            <p class="mt-0.5 text-xs text-slate-400">
                                                {{ $detail->unit->name }}
                                            </p>

                                        @endif

                                    </td>

                                    <td class="py-4 text-right text-sm text-slate-600">
                                        {{ number_format((float) $detail->quantity, 2) }}
                                    </td>

                                    <td class="py-4 text-right text-sm text-slate-600">
                                        Rs. {{ number_format((float) $detail->rate, 2) }}
                                    </td>

                                    <td class="py-4 text-right text-sm text-slate-600">

                                        {{ number_format((float) $detail->tax_rate, 2) }}%

                                        <div class="text-xs text-slate-400">
                                            Rs. {{ number_format((float) $detail->tax_amount, 2) }}
                                        </div>

                                    </td>

                                    <td class="py-4 text-right font-semibold text-slate-800">
                                        Rs. {{ number_format((float) $detail->amount, 2) }}
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>


                {{-- Totals --}}

                <div class="mt-6 ml-auto max-w-sm border-t border-slate-200 pt-5">

                    <div class="flex justify-between py-2 text-sm">

                        <span class="text-slate-500">
                            Sub Total
                        </span>

                        <span class="font-medium text-slate-800">
                            Rs. {{ number_format((float) $order->sub_total, 2) }}
                        </span>

                    </div>

                    <div class="flex justify-between py-2 text-sm">

                        <span class="text-slate-500">
                            Discount
                        </span>

                        <span class="font-medium text-slate-800">
                            Rs. {{ number_format((float) $order->discount, 2) }}
                        </span>

                    </div>

                    <div class="flex justify-between py-2 text-sm">

                        <span class="text-slate-500">
                            Tax
                        </span>

                        <span class="font-medium text-slate-800">
                            Rs. {{ number_format((float) $order->total_tax, 2) }}
                        </span>

                    </div>

                    <div class="flex justify-between py-2 text-sm">

                        <span class="text-slate-500">
                            Delivery
                        </span>

                        <span class="font-medium text-slate-800">
                            Rs. {{ number_format((float) $order->delivery_charge, 2) }}
                        </span>

                    </div>

                    <div class="mt-3 flex justify-between rounded-xl bg-slate-900 px-4 py-4">

                        <span class="font-semibold text-slate-300">
                            Grand Total
                        </span>

                        <span class="text-xl font-bold text-white">
                            Rs. {{ number_format((float) $order->grand_total, 2) }}
                        </span>

                    </div>

                </div>

            </div>

        </div>


        {{-- =========================================================
             NOTES
        ========================================================== --}}

        @if($order->order_notes)

            <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <h2 class="text-base font-semibold text-slate-800">
                    Order Notes
                </h2>

                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-600">
                    {{ $order->order_notes }}
                </p>

            </div>

        @endif

    </div>

</div>

@endsection