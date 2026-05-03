@extends('layout.app')

@push('title', 'Edit Products')

@section('content')

    <div class="max-w-3xl mx-auto py-10">

        <h1 class="text-2xl font-bold mb-6 text-teal-600">Edit Product</h1>

        <form method="POST" action="{{ route('products.update', $product->id) }}" class="bg-white p-6 shadow rounded">

            @csrf
            @method('PUT')

            <input type="text" name="product_name" value="{{ $product->product_name }}" class="w-full border p-2 mb-3"
                placeholder="Product Name">

            <input type="text" name="product_code" value="{{ $product->product_code }}"
                class="w-full border p-2 mb-3" placeholder="Product Code">

            <input type="number" name="mrp" value="{{ $product->mrp }}" class="w-full border p-2 mb-3"
                placeholder="MRP">

            <input type="number" name="inventory_available_quantity"
                value="{{ $product->inventory_available_quantity }}" class="w-full border p-2 mb-3" placeholder="Stock">

            <select name="status" class="w-full border p-2 mb-3">
                <option {{ $product->status == 'Active' ? 'selected' : '' }}>Active</option>
                <option {{ $product->status == 'Inactive' ? 'selected' : '' }}>Inactive</option>
            </select>

            <button class="bg-teal-600 text-white px-4 py-2 rounded">
                Update Product
            </button>

        </form>

    </div>
@endsection
