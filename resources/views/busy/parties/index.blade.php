@extends('layout.app')

@section('content')

<div class="min-h-screen bg-slate-50 py-6">

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        <div class="mb-6 flex items-center justify-between">

            <div>

                <h1 class="text-2xl font-bold text-slate-900">
                    BUSY Parties
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Manage customers and synchronize them with BUSY.
                </p>

            </div>

            <a
                href="{{ route('busy.parties.create') }}"
                class="rounded-lg bg-teal-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-800"
            >
                + Create Party
            </a>

        </div>

        @include('busy.parties.partials.messages')

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-slate-200">

                    <thead class="bg-slate-50">

                        <tr>

                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                Party
                            </th>

                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                Contact
                            </th>

                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                BUSY ID
                            </th>

                            <th class="px-5 py-3 text-center text-xs font-semibold uppercase text-slate-500">
                                Status
                            </th>

                            <th class="px-5 py-3 text-center text-xs font-semibold uppercase text-slate-500">
                                Sync
                            </th>

                            <th class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-500">
                                Action
                            </th>

                        </tr>

                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        @forelse($parties as $party)

                            <tr class="hover:bg-slate-50">

                                <td class="px-5 py-4">

                                    <div class="font-semibold text-slate-800">
                                        {{ $party->company_name ?: $party->name }}
                                    </div>

                                    @if($party->alias)

                                        <div class="mt-1 text-xs text-slate-400">
                                            {{ $party->alias }}
                                        </div>

                                    @endif

                                </td>

                                <td class="px-5 py-4">

                                    <div class="text-sm text-slate-700">
                                        {{ $party->mobile ?: $party->phone ?: '—' }}
                                    </div>

                                    @if($party->email)

                                        <div class="mt-1 text-xs text-slate-400">
                                            {{ $party->email }}
                                        </div>

                                    @endif

                                </td>

                                <td class="px-5 py-4 text-sm">

                                    @if($party->busyparty_id)

                                        <span class="font-semibold text-slate-700">
                                            {{ $party->busyparty_id }}
                                        </span>

                                    @else

                                        <span class="text-slate-400">
                                            Not synced
                                        </span>

                                    @endif

                                </td>

                                <td class="px-5 py-4 text-center">

                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold
                                        {{ $party->status === 'Active'
                                            ? 'bg-emerald-50 text-emerald-700'
                                            : 'bg-slate-100 text-slate-600' }}"
                                    >
                                        {{ $party->status }}
                                    </span>

                                </td>

                                <td class="px-5 py-4 text-center">

                                    @if($party->busy_sync_status === 'Synced')

                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                            Synced
                                        </span>

                                    @elseif($party->busy_sync_status === 'Failed')

                                        <span
                                            title="{{ $party->busy_sync_message }}"
                                            class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700"
                                        >
                                            Failed
                                        </span>

                                    @else

                                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                            Pending
                                        </span>

                                    @endif

                                </td>

                                <td class="px-5 py-4 text-right">

                                    <a
                                        href="{{ route('busy.parties.edit', $party) }}"
                                        class="inline-flex rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                                    >
                                        Edit
                                    </a>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="6"
                                    class="px-5 py-12 text-center text-sm text-slate-500"
                                >
                                    No BUSY parties found.
                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            @if($parties->hasPages())

                <div class="border-t border-slate-200 p-4">
                    {{ $parties->links() }}
                </div>

            @endif

        </div>

    </div>

</div>

@endsection