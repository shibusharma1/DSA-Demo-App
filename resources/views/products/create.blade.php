<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Product</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#009688',
                    },
                },
            },
        }
    </script>
</head>

<body class="bg-gray-50 text-gray-800">

    <!-- Header -->
    <header class="bg-primary text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 py-6 flex justify-between items-center">
            <h1 class="text-2xl font-bold">Webhook Microservice App</h1>
            <nav>
                <a href="{{ route('products.index') }}"
                    class="bg-white text-primary font-semibold px-4 py-2 rounded-md shadow hover:bg-gray-100 transition">
                    Product List
                </a>
            </nav>
        </div>
    </header>

    <!-- Main -->
    <div class="max-w-3xl mx-auto py-12">
        <h1 class="text-3xl font-bold mb-6 text-primary" style="color:#009688;">
            Create New Product
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

        <!-- Form -->
        <form action="{{ route('products.store') }}" method="POST"
            class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">

            @csrf

            <!-- Product Name -->
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Product Name *</label>
                <input type="text" name="product_name" value="{{ old('product_name') }}"
                    class="shadow border rounded w-full py-2 px-3 focus:outline-none focus:shadow-outline"
                    required>
            </div>

            <!-- Product Code -->
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Product Code</label>
                <input type="text" name="product_code" value="{{ old('product_code') }}"
                    class="shadow border rounded w-full py-2 px-3 focus:outline-none focus:shadow-outline">
            </div>

            <!-- MRP -->
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">MRP</label>
                <input type="number" step="0.01" name="mrp" value="{{ old('mrp') }}"
                    class="shadow border rounded w-full py-2 px-3 focus:outline-none focus:shadow-outline">
            </div>

            <!-- Dealer Price -->
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Dealer Price</label>
                <input type="number" step="0.01" name="d_price" value="{{ old('d_price') }}"
                    class="shadow border rounded w-full py-2 px-3 focus:outline-none focus:shadow-outline">
            </div>

            <!-- Retail Price -->
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Retail Price</label>
                <input type="number" step="0.01" name="r_price" value="{{ old('r_price') }}"
                    class="shadow border rounded w-full py-2 px-3 focus:outline-none focus:shadow-outline">
            </div>

            <!-- Unit -->
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Unit</label>
                <input type="text" name="unit_name" value="{{ old('unit_name') }}"
                    class="shadow border rounded w-full py-2 px-3 focus:outline-none focus:shadow-outline"
                    placeholder="e.g. 32 KG">
            </div>

            <!-- Stock -->
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Available Quantity</label>
                <input type="number" name="inventory_available_quantity"
                    value="{{ old('inventory_available_quantity', 0) }}"
                    class="shadow border rounded w-full py-2 px-3 focus:outline-none focus:shadow-outline">
            </div>

            <!-- Status -->
            <div class="mb-6">
                <label class="block text-gray-700 font-bold mb-2">Status *</label>
                <select name="status"
                    class="shadow border rounded w-full py-2 px-3 focus:outline-none focus:shadow-outline">
                    <option value="Active" {{ old('status') == 'Active' ? 'selected' : '' }}>Active</option>
                    <option value="Inactive" {{ old('status') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <!-- Buttons -->
            <div class="flex items-center justify-between">
                <button type="submit"
                    class="bg-primary hover:bg-teal-700 text-white font-bold py-2 px-4 rounded"
                    style="background:#009688;">
                    Create Product
                </button>

                <a href="{{ route('products.index') }}"
                    class="text-primary font-bold text-sm hover:text-teal-700"
                    style="color:#009688;">
                    Back to Products
                </a>
            </div>

        </form>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-100 text-gray-600 py-6">
        <div class="max-w-7xl mx-auto px-4 text-center">
            &copy; 2026 Shibu Sharma. All rights reserved.
        </div>
    </footer>

</body>

</html>