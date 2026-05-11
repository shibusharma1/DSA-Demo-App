@extends('layout.app')

@push('title', 'Home')

@section('content')

    <form method="POST" action="{{ route('payments.store') }}" class="max-w-4xl mx-auto my-3 bg-white p-6 shadow">
        @csrf

        <select name="customer_id" class="w-full border p-2 mb-3">
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}">{{ $customer->contact_name }}</option>
            @endforeach
        </select>

        {{-- <select name="order_id" class="w-full border p-2 mb-3">
            <option value="">Select Order</option>
            @foreach ($orders as $order)
                <option value="{{ $order->id }}">{{ $order->order_no }}</option>
            @endforeach
        </select> --}}

        <input type="number" name="payment_received" placeholder="Amount" class="w-full border p-2 mb-3">

       <input type="date" name="payment_date" class="w-full border p-2 mb-3"
       value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}">

        <select name="payment_method" class="w-full border p-2 mb-3">
            <option>Cash</option>
            <option>Bank</option>
        </select>

        <select name="payment_status" class="w-full border p-2 mb-3">
            <option>Cleared</option>
            <option>Pending</option>
        </select>

        <button class="bg-teal-600 text-white px-4 py-2 rounded">Save Payment</button>
    </form>
@endsection
