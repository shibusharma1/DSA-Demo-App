@extends('layout.app')

@section('content')

<div class="min-h-screen bg-slate-50 py-6">

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                    Orders
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Manage customer orders and BUSY synchronization.
                </p>
            </div>

            <a
                href="{{ route('orders.create') }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            >
                <svg
                    class="h-5 w-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 4v16m8-8H4"
                    />
                </svg>

                Create Order
            </a>

        </div>


        {{-- Summary Cards --}}
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

            {{-- Total --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-sm font-medium text-slate-500">
                            Total Orders
                        </p>

                        <p class="mt-2 text-2xl font-bold text-slate-900">
                            {{ number_format($totalOrders) }}
                        </p>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-slate-100">
                        <svg
                            class="h-6 w-6 text-slate-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l4.414 4.414A1 1 0 0119 8v11a2 2 0 01-2 2z"
                            />
                        </svg>
                    </div>

                </div>

            </div>


            {{-- Synced --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-sm font-medium text-slate-500">
                            Synced to BUSY
                        </p>

                        <p class="mt-2 text-2xl font-bold text-emerald-600">
                            {{ number_format($syncedOrders) }}
                        </p>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-emerald-50">

                        <svg
                            class="h-6 w-6 text-emerald-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>

                    </div>

                </div>

            </div>


            {{-- Pending --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-sm font-medium text-slate-500">
                            Pending Sync
                        </p>

                        <p class="mt-2 text-2xl font-bold text-amber-600">
                            {{ number_format($pendingOrders) }}
                        </p>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-amber-50">

                        <svg
                            class="h-6 w-6 text-amber-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8v4l3 2"
                            />
                        </svg>

                    </div>

                </div>

            </div>


            {{-- Failed --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-sm font-medium text-slate-500">
                            Sync Failed
                        </p>

                        <p class="mt-2 text-2xl font-bold text-red-600">
                            {{ number_format($failedOrders) }}
                        </p>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-red-50">

                        <svg
                            class="h-6 w-6 text-red-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>

                    </div>

                </div>

            </div>

        </div>


        {{-- Filters --}}
        <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">

            <form
                method="GET"
                action="{{ route('orders.index') }}"
            >

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-6">

                    {{-- Search --}}
                    <div class="lg:col-span-2">

                        <label
                            for="search"
                            class="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            Search
                        </label>

                        <div class="relative">

                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">

                                <svg
                                    class="h-5 w-5 text-slate-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M21 21l-4.35-4.35m2.35-5.65a7 7 0 11-14 0 7 7 0 0114 0z"
                                    />
                                </svg>

                            </div>

                            <input
                                type="text"
                                name="search"
                                id="search"
                                value="{{ request('search') }}"
                                placeholder="Order no, BUSY ID or customer..."
                                class="w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                            >

                        </div>

                    </div>


                    {{-- From Date --}}
                    <div>

                        <label
                            for="from_date"
                            class="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            From Date
                        </label>

                        <input
                            type="date"
                            name="from_date"
                            id="from_date"
                            value="{{ request('from_date') }}"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        >

                    </div>


                    {{-- To Date --}}
                    <div>

                        <label
                            for="to_date"
                            class="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            To Date
                        </label>

                        <input
                            type="date"
                            name="to_date"
                            id="to_date"
                            value="{{ request('to_date') }}"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        >

                    </div>


                    {{-- Order Status --}}
                    <div>

                        <label
                            for="status"
                            class="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            Order Status
                        </label>

                        <select
                            name="status"
                            id="status"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        >

                            <option value="">
                                All Status
                            </option>

                            <option
                                value="draft"
                                @selected(request('status') === 'draft')
                            >
                                Draft
                            </option>

                            <option
                                value="confirmed"
                                @selected(request('status') === 'confirmed')
                            >
                                Confirmed
                            </option>

                            <option
                                value="completed"
                                @selected(request('status') === 'completed')
                            >
                                Completed
                            </option>

                            <option
                                value="cancelled"
                                @selected(request('status') === 'cancelled')
                            >
                                Cancelled
                            </option>

                        </select>

                    </div>


                    {{-- BUSY Sync --}}
                    <div>

                        <label
                            for="busy_sync_status"
                            class="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            BUSY Sync
                        </label>

                        <select
                            name="busy_sync_status"
                            id="busy_sync_status"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        >

                            <option value="">
                                All
                            </option>

                            <option
                                value="synced"
                                @selected(request('busy_sync_status') === 'synced')
                            >
                                Synced
                            </option>

                            <option
                                value="pending"
                                @selected(request('busy_sync_status') === 'pending')
                            >
                                Pending
                            </option>

                            <option
                                value="failed"
                                @selected(request('busy_sync_status') === 'failed')
                            >
                                Failed
                            </option>

                        </select>

                    </div>

                </div>


                {{-- Buttons --}}
                <div class="mt-4 flex flex-wrap items-center gap-2">

                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
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
                                d="M21 21l-4.35-4.35m2.35-5.65a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>

                        Apply Filters

                    </button>


                    @if(request()->hasAny([
                        'search',
                        'from_date',
                        'to_date',
                        'status',
                        'busy_sync_status'
                    ]))

                        <a
                            href="{{ route('orders.index') }}"
                            class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                        >
                            Clear Filters
                        </a>

                    @endif

                </div>

            </form>

        </div>


        {{-- Orders Table --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-slate-200">

                    <thead class="bg-slate-50">

                        <tr>

                            <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Order
                            </th>

                            <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Customer
                            </th>

                            <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Date
                            </th>

                            <th class="whitespace-nowrap px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Items
                            </th>

                            <th class="whitespace-nowrap px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Grand Total
                            </th>

                            <th class="whitespace-nowrap px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Status
                            </th>

                            <th class="whitespace-nowrap px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-500">
                                BUSY
                            </th>

                            <th class="whitespace-nowrap px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-slate-100">

                        @forelse($orders as $order)

                            <tr class="transition hover:bg-slate-50">

                                {{-- Order --}}
                                <td class="px-5 py-4">

                                    <a
                                        href="{{ route('orders.show', $order) }}"
                                        class="font-semibold text-indigo-600 hover:text-indigo-700"
                                    >
                                        {{ $order->order_no }}
                                    </a>

                                    @if($order->busyorder_id)

                                        <div class="mt-1 text-xs text-slate-400">
                                            BUSY ID:
                                            {{ $order->busyorder_id }}
                                        </div>

                                    @endif

                                </td>


                                {{-- Customer --}}
                                <td class="px-5 py-4">

                                    @if($order->client)

                                        <div class="font-medium text-slate-800">
                                            {{ $order->client->name ?? $order->client->company_name ?? '—' }}
                                        </div>

                                        @if(!empty($order->client->company_name) &&
                                            $order->client->company_name !== $order->client->name)

                                            <div class="mt-0.5 text-xs text-slate-400">
                                                {{ $order->client->company_name }}
                                            </div>

                                        @endif

                                    @else

                                        <span class="text-sm text-slate-400">
                                            Customer deleted
                                        </span>

                                    @endif

                                </td>


                                {{-- Date --}}
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">

                                    {{ \Carbon\Carbon::parse($order->order_date)->format('d M Y') }}

                                </td>


                                {{-- Items --}}
                                <td class="px-5 py-4 text-center">

                                    <span class="inline-flex min-w-8 items-center justify-center rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">
                                        {{ $order->details_count }}
                                    </span>

                                </td>


                                {{-- Total --}}
                                <td class="whitespace-nowrap px-5 py-4 text-right">

                                    <span class="font-semibold text-slate-900">
                                        {{ number_format($order->grand_total, 2) }}
                                    </span>

                                </td>


                                {{-- Status --}}
                                <td class="px-5 py-4 text-center">

                                    @php
                                        $statusClasses = match(strtolower($order->status ?? '')) {
                                            'draft' => 'bg-slate-100 text-slate-700',
                                            'confirmed' => 'bg-blue-50 text-blue-700',
                                            'completed' => 'bg-emerald-50 text-emerald-700',
                                            'cancelled' => 'bg-red-50 text-red-700',
                                            default => 'bg-slate-100 text-slate-600',
                                        };
                                    @endphp

                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses }}">
                                        {{ ucfirst($order->status ?? 'Unknown') }}
                                    </span>

                                </td>


                                {{-- BUSY --}}
                                <td class="px-5 py-4 text-center">

                                    @if($order->busy_sync_status === 'synced')

                                        <div class="inline-flex flex-col items-center">

                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">

                                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>

                                                Synced

                                            </span>

                                            @if($order->busy_synced_at)

                                                <span class="mt-1 text-[11px] text-slate-400">
                                                    {{ \Carbon\Carbon::parse($order->busy_synced_at)->format('d M Y H:i') }}
                                                </span>

                                            @endif

                                        </div>

                                    @elseif($order->busy_sync_status === 'failed')

                                        <div class="inline-flex flex-col items-center">

                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">

                                                <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>

                                                Failed

                                            </span>

                                        </div>

                                    @else

                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">

                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>

                                            Pending

                                        </span>

                                    @endif

                                </td>


                                {{-- Actions --}}
                                <td class="px-5 py-4 text-right">

                                    <div class="flex items-center justify-end gap-2">

                                        {{-- View --}}
                                        <a
                                            href="{{ route('orders.show', $order) }}"
                                            class="inline-flex items-center rounded-lg border border-slate-200 bg-white p-2 text-slate-600 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-600"
                                            title="View Order"
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
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                                />

                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                                />
                                            </svg>

                                        </a>


                                        {{-- Sync --}}
                                        @if($order->busy_sync_status !== 'synced')

                                            <form
                                                action="{{ route('orders.sync-busy', $order) }}"
                                                method="POST"
                                            >

                                                @csrf

                                                <button
                                                    type="submit"
                                                    class="inline-flex items-center rounded-lg border border-amber-200 bg-amber-50 p-2 text-amber-700 transition hover:bg-amber-100"
                                                    title="Sync to BUSY"
                                                    onclick="return confirm('Sync this order to BUSY?')"
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
                                                            d="M4 4v5h5M20 20v-5h-5M5.64 9A7 7 0 0118.36 7M18.36 15A7 7 0 015.64 17"
                                                        />
                                                    </svg>

                                                </button>

                                            </form>

                                        @endif

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="8"
                                    class="px-5 py-16 text-center"
                                >

                                    <div class="mx-auto flex max-w-sm flex-col items-center">

                                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-100">

                                            <svg
                                                class="h-7 w-7 text-slate-400"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="1.8"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l4.414 4.414A1 1 0 0119 8v11a2 2 0 01-2 2z"
                                                />
                                            </svg>

                                        </div>

                                        <h3 class="mt-4 text-sm font-semibold text-slate-900">
                                            No orders found
                                        </h3>

                                        <p class="mt-1 text-sm text-slate-500">
                                            Try changing your filters or create a new order.
                                        </p>

                                        <a
                                            href="{{ route('orders.create') }}"
                                            class="mt-4 inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                                        >
                                            Create Order
                                        </a>

                                    </div>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            {{-- Pagination --}}
            @if($orders->hasPages())

                <div class="border-t border-slate-200 px-5 py-4">

                    {{ $orders->links() }}

                </div>

            @endif

        </div>

    </div>

</div>

@endsection