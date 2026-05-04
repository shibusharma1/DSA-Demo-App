@extends('layout.app')

@push('title', 'Products List')

@section('content')
    <div class="max-w-5xl mx-auto py-12">
        <div class="flex justify-between mb-6">
            <h1 class="text-2xl font-bold text-teal-600">Products</h1>

            <a href="{{ route('products.create') }}" class="bg-teal-600 text-white px-4 py-2 rounded">
                Add Product
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
                    <th class="p-3">Name</th>
                    <th class="p-3">Code</th>
                    <th class="p-3">MRP</th>
                    <th class="p-3">Stock</th>
                    <th class="p-3">Zoho Id</th>
                    <th class="p-3">ErpNext Id</th>
                    <th class="p-3">Actions</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($products as $product)
                    <tr class="border-b">
                        <td class="p-3">{{ $product->product_name }}</td>
                        <td class="p-3">{{ $product->product_code }}</td>
                        <td class="p-3">{{ $product->mrp }}</td>
                        <td class="p-3">{{ $product->inventory_available_quantity }}</td>
                        <td class="p-3">{{ $product->zoho_id }}</td>
                        <td class="p-3">{{ $product->erpnext_id }}</td>

                        <td class="p-3 flex gap-2">
                            <a href="{{ route('products.edit', $product->id) }}"
                                class="bg-yellow-500 text-white px-2 py-1 rounded">
                                Edit
                            </a>

                            <form method="POST" action="{{ route('products.destroy', $product->id) }}">
                                @csrf @method('DELETE')
                                <button class="bg-red-500 text-white px-2 py-1 rounded">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-5">
            {{ $products->links() }}
        </div>

    </div>
@endsection
