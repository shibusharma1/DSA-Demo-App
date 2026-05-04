@extends('layout.app')

@push('title', 'payment List')

@section('content')
    <div class="max-w-7xl mx-auto py-10">

        <div class="flex justify-between mb-6">
            <h1 class="text-2xl font-bold text-teal-600">Payments</h1>

            <a href="{{ route('payments.create') }}" class="bg-teal-600 text-white px-4 py-2 rounded">
                Add Payment
            </a>
        </div>

        @if (session('success'))
            <div class="bg-green-100 p-3 mb-4 rounded">
                {{ session('success') }}
            </div>
        @endif

        <table class="w-full bg-white shadow rounded">
            <thead class="bg-teal-600 text-white">
                <tr>
                    <th class="p-3">Client</th>
                    <th class="p-3">Amount</th>
                    <th class="p-3">Method</th>
                    <th class="p-3">Date</th>
                    <th class="p-3">Status</th>
                    <th class="p-3">Actions</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($payments as $payment)
                    <tr class="border-b">
                        <td class="p-3">{{ $payment->client->name ?? '' }}</td>
                        <td class="p-3">{{ $payment->payment_received }}</td>
                        <td class="p-3">{{ $payment->payment_method }}</td>
                        <td class="p-3">{{ $payment->payment_date }}</td>
                        <td class="p-3">{{ $payment->payment_status }}</td>

                        <td class="p-3 flex gap-2">
                            <a href="{{ route('payments.edit', $payment->id) }}"
                                class="bg-yellow-500 text-white px-2 py-1 rounded">Edit</a>

                            <form method="POST" action="{{ route('payments.destroy', $payment->id) }}">
                                @csrf @method('DELETE')
                                <button class="bg-red-500 text-white px-2 py-1 rounded">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $payments->links() }}
    </div>
@endsection
