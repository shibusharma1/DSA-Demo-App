@extends('layout.app')

@push('title', 'Home')

@section('content')

    <form method="POST" action="{{ route('payments.update', $payment->id) }}" class="max-w-4xl mx-auto bg-white p-6 shadow">
        @csrf
        @method('PUT')

        <select name="client_id" class="w-full border p-2 mb-3">
            @foreach ($clients as $client)
                <option value="{{ $client->id }}" {{ $payment->client_id == $client->id ? 'selected' : '' }}>
                    {{ $client->name }}
                </option>
            @endforeach
        </select>

        <input type="number" name="payment_received" value="{{ $payment->payment_received }}" class="w-full border p-2 mb-3">

        <input type="date" name="payment_date" value="{{ $payment->payment_date }}" class="w-full border p-2 mb-3">

        <select name="payment_method" class="w-full border p-2 mb-3">
            <option {{ $payment->payment_method == 'Cash' ? 'selected' : '' }}>Cash</option>
            <option {{ $payment->payment_method == 'Bank' ? 'selected' : '' }}>Bank</option>
        </select>

        <select name="payment_status" class="w-full border p-2 mb-3">
            <option {{ $payment->payment_status == 'Cleared' ? 'selected' : '' }}>Cleared</option>
            <option {{ $payment->payment_status == 'Pending' ? 'selected' : '' }}>Pending</option>
        </select>

        <button class="bg-teal-600 text-white px-4 py-2 rounded">Update Payment</button>
    </form>

@endsection
