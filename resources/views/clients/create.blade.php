@extends('layout.app')

@push('title', 'Create Client')

@section('content')

    <!-- Main -->
    <div class="max-w-3xl mx-auto py-12">
        <h1 class="text-3xl font-bold mb-6 text-primary" style="color:#009688;">
            Create New Client
        </h1>

        {{-- Success Message --}}
        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        {{-- Validation Errors --}}
        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        <form action="{{ route('clients.store') }}" method="POST" class="bg-white p-6 shadow rounded">
            @csrf

            <input type="text" name="name" placeholder="Client Name" class="w-full border p-2 mb-3" required>
            <input type="text" name="company_name" placeholder="Company Name" class="w-full border p-2 mb-3">
            <input type="email" name="email" placeholder="Email" class="w-full border p-2 mb-3">
            <input type="text" name="phone" placeholder="Phone" class="w-full border p-2 mb-3">
            <input type="text" name="mobile" placeholder="Mobile" class="w-full border p-2 mb-3">
            <input type="text" name="address_1" placeholder="Address 1" class="w-full border p-2 mb-3">
            <input type="text" name="location" placeholder="Location" class="w-full border p-2 mb-3">

            <input type="number" name="credit_limit" placeholder="Credit Limit" class="w-full border p-2 mb-3">
            <input type="number" name="credit_days" placeholder="Credit Days" class="w-full border p-2 mb-3">

            <select name="status" class="w-full border p-2 mb-3">
                <option>Active</option>
                <option>Inactive</option>
            </select>

            <button class="bg-teal-600 text-white px-4 py-2 rounded">Save</button>
        </form>
    </div>

@endsection