<!-- resources/views/products/edit.blade.php -->
@extends('layout.app')

@push('title', 'Edit Client')

@section('content')

    <div class="max-w-3xl mx-auto py-10">

        <h1 class="text-2xl font-bold mb-6 text-teal-600">Edit Client</h1>


        <form method="POST" action="{{ route('clients.update', $client->id) }}" class="bg-white p-6 shadow rounded">
            @csrf
            @method('PUT')

            <input type="text" name="name" value="{{ $client->name }}" class="w-full border p-2 mb-3">

            <input type="text" name="company_name" value="{{ $client->company_name }}"
                class="w-full border p-2 mb-3">

            <input type="email" name="email" value="{{ $client->email }}" class="w-full border p-2 mb-3">

            <input type="text" name="phone" value="{{ $client->phone }}" class="w-full border p-2 mb-3">

            <select name="status" class="w-full border p-2 mb-3">
                <option {{ $client->status == 'Active' ? 'selected' : '' }}>Active</option>
                <option {{ $client->status == 'Inactive' ? 'selected' : '' }}>Inactive</option>
            </select>

            <button class="bg-teal-600 text-white px-4 py-2 rounded">Update</button>
        </form>
    </div>
@endsection
