<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers</title>
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

    <div class="max-w-5xl mx-auto py-12">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold text-primary" style="color:#009688;">
                Customers List
            </h1>
            <a href="{{ route('customers.create') }}"
                class="bg-primary hover:bg-teal-700 text-white font-bold py-2 px-4 rounded" style="background:#009688;">
                Add New Customer
            </a>
        </div>

        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <table class="min-w-full leading-normal">
                <thead>
                    <tr class="bg-primary text-white">
                        <th class="px-5 py-3 text-left text-sm font-semibold uppercase">ID</th>
                        <th class="px-5 py-3 text-left text-sm font-semibold uppercase">Name</th>
                        <th class="px-5 py-3 text-left text-sm font-semibold uppercase">Email</th>
                        <th class="px-5 py-3 text-left text-sm font-semibold uppercase">Company</th>
                        <th class="px-5 py-3 text-left text-sm font-semibold uppercase">Phone</th>
                        <th class="px-5 py-3 text-left text-sm font-semibold uppercase">Zoho Book Id</th>
                        <th class="px-5 py-3 text-left text-sm font-semibold uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($customers as $customer)
                        <tr class="border-b">
                            <td class="px-5 py-3">{{ $customer->id }}</td>
                            <td class="px-5 py-3">{{ $customer->contact_name }}</td>
                            <td class="px-5 py-3">{{ $customer->email }}</td>
                            <td class="px-5 py-3">{{ $customer->company_name ?? '-' }}</td>
                            <td class="px-5 py-3">{{ $customer->phone ?? '-' }}</td>
                            <td class="px-5 py-3">{{ $customer->zb_id ?? '-' }}</td>
                            <td class="px-5 py-3 flex space-x-2">
                                <a href="{{ route('customers.edit', $customer->id) }}"
                                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded">Edit</a>
                                <form action="{{ route('customers.destroy', $customer->id) }}" method="POST"
                                    onsubmit="return confirm('Are you sure?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $customers->links() }}
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-100 text-gray-600 py-6">
        <div class="max-w-7xl mx-auto px-4 text-center">
            &copy; 2026 Shibu Sharma. All rights reserved.
        </div>
    </footer>

</body>

</html>
