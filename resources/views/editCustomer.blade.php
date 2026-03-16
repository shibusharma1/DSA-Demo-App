<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Customer</title>
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
                <a href="{{ url('/customers') }}"
                    class="bg-white text-primary font-semibold px-4 py-2 rounded-md shadow hover:bg-gray-100 transition">
                    Customer List
                </a>
            </nav>
        </div>
    </header>

    <div class="max-w-3xl mx-auto py-12">
        <h1 class="text-3xl font-bold mb-6 text-primary" style="color:#009688;">Edit Customer</h1>

        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('customers.update', $customer->id) }}" method="POST"
            class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Contact Name</label>
                <input type="text" name="contact_name" value="{{ old('contact_name', $customer->contact_name) }}"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                    required>
                @error('contact_name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Email</label>
                <input type="email" name="email" value="{{ old('email', $customer->email) }}"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                    required>
                @error('email')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Company Name</label>
                <input type="text" name="company_name" value="{{ old('company_name', $customer->company_name) }}"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                @error('company_name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Phone</label>
                <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                @error('phone')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Zoho Books ID</label>
                <input type="text" name="zb_id" value="{{ old('zb_id', $customer->zb_id) }}"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                    readonly>
                @error('zb_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between">
                <button type="submit"
                    class="bg-primary hover:bg-teal-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                    style="background:#009688;">
                    Update Customer
                </button>
                <a href="{{ route('customers.index') }}"
                    class="inline-block align-baseline font-bold text-sm text-primary hover:text-teal-700"
                    style="color:#009688;">
                    Back to Customers
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
