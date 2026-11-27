@extends('layout.app')

@push('title', 'Client List')

@section('content')
<div class="max-w-7xl mx-auto py-10">

    <div class="flex justify-between mb-6">
        <h1 class="text-2xl font-bold text-teal-600">parties_busy</h1>

        <a href="{{ route('parties_busy.create') }}"
           class="bg-teal-600 text-white px-4 py-2 rounded">
            Add Client
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 p-3 mb-4 rounded">
            {{ session('success') }}
        </div>
    @endif

    <table class="w-full bg-white shadow rounded">
        <thead class="bg-teal-600 text-white">
            <tr>
                <th class="p-3">Name</th>
                <th class="p-3">Company</th>
                <th class="p-3">Phone</th>
                <th class="p-3">Email</th>
                <th class="p-3">Status</th>
                <th class="p-3">Actions</th>
            </tr>
        </thead>

        <tbody>
            @foreach($parties_busy as $client)
            <tr class="border-b">
                <td class="p-3">{{ $client->name }}</td>
                <td class="p-3">{{ $client->company_name }}</td>
                <td class="p-3">{{ $client->phone }}</td>
                <td class="p-3">{{ $client->email }}</td>
                <td class="p-3">{{ $client->status }}</td>

                <td class="p-3 flex gap-2">
                    <a href="{{ route('parties_busy.edit',$client->id) }}"
                       class="bg-yellow-500 text-white px-2 py-1 rounded">Edit</a>

                    <form method="POST" action="{{ route('parties_busy.destroy',$client->id) }}">
                        @csrf @method('DELETE')
                        <button class="bg-red-500 text-white px-2 py-1 rounded">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-5">
        {{ $parties_busy->links() }}
    </div>
</div>
@endsection