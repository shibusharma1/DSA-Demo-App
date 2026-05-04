@extends('layout.app')

@push('title', 'Orders List')

@section('content')
    <!-- resources/views/orders/index.blade.php -->

        <div class="max-w-5xl mx-auto py-12">


        <div class="flex justify-between mb-6">
            <h1 class="text-2xl font-bold text-teal-600">Orders</h1>

            <a href="{{ route('orders.create') }}" class="bg-teal-600 text-white px-4 py-2 rounded">
                Add Order
            </a>
        </div>

        @foreach ($orders as $order)
            <div class="bg-white p-4 mb-3 shadow rounded">
                <p><strong>Order #:</strong> {{ $order->order_no }}</p>
                <p><strong>Client:</strong> {{ $order->client->name ?? '' }}</p>
                <p><strong>Total:</strong> {{ $order->grand_total }}</p>
                <p><strong>Status:</strong> {{ $order->delivery_status }}</p>
            </div>
        @endforeach

        {{ $orders->links() }}
    </div>
@endsection
