<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Api Credentials</title>
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

<body>


    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-bold mb-6" style="color:#009688;">API Credentials</h1>

        <div class="mb-6">
            <a href="{{ route('api-credentials.create') }}"
                class="bg-[#009688] hover:bg-teal-700 text-white font-bold py-2 px-4 rounded">
                Add New Credential
            </a>
        </div>

        @if (session('success'))
            <div class="bg-green-200 text-green-800 p-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <table class="min-w-full bg-white border border-gray-200">
            <thead>
                <tr>
                    <th class="py-2 px-4 border-b">ID</th>
                    <th class="py-2 px-4 border-b">Company ID</th>
                    <th class="py-2 px-4 border-b">Service Type</th>
                    <th class="py-2 px-4 border-b">Access Token</th>
                    <th class="py-2 px-4 border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($credentials as $cred)
                    <tr>
                        <td class="py-2 px-4 border-b">{{ $cred->id }}</td>
                        <td class="py-2 px-4 border-b">{{ $cred->company_id }}</td>
                        <td class="py-2 px-4 border-b">{{ $cred->service_type }}</td>
                        <td class="py-2 px-4 border-b">{{ $cred->access_token ?? 'N/A' }}</td>
                        <td class="py-2 px-4 border-b flex gap-2">
                            <a href="{{ route('api-credentials.edit', $cred->id) }}"
                                class="bg-blue-500 hover:bg-blue-700 text-white py-1 px-3 rounded">Edit</a>
                            <form action="{{ route('api-credentials.destroy', $cred->id) }}" method="POST"
                                onsubmit="return confirm('Are you sure?')">
                                @csrf
                                @method('DELETE')
                                <button class="bg-red-500 hover:bg-red-700 text-white py-1 px-3 rounded">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">
            {{ $credentials->links() }}
        </div>
    </div>

</body>

</html>
