@extends('layout.app')

@section('content')

<div class="container-fluid">

    {{-- ===================================================== --}}
    {{-- HEADER --}}
    {{-- ===================================================== --}}

    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">

            <h4 class="mb-0">
                Collections
            </h4>

            <a
                href="{{ route('collections.create') }}"
                class="btn btn-primary"
            >
                <i class="fas fa-plus"></i>
                Create
            </a>

        </div>


        {{-- ================================================= --}}
        {{-- FILTER --}}
        {{-- ================================================= --}}

        <div class="card-body border-bottom">

            <form
                method="GET"
                action="{{ route('collections.index') }}"
            >

                <div class="row">

                    <div class="col-md-3 mb-2">

                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            class="form-control"
                            placeholder="Search party..."
                        >

                    </div>


                    <div class="col-md-2 mb-2">

                        <select
                            name="payment_method"
                            class="form-control"
                        >

                            <option value="">
                                All Modes
                            </option>

                            <option
                                value="Cash"
                                {{ request('payment_method') === 'Cash'
                                    ? 'selected'
                                    : '' }}
                            >
                                Cash
                            </option>

                            <option
                                value="Bank"
                                {{ request('payment_method') === 'Bank'
                                    ? 'selected'
                                    : '' }}
                            >
                                Bank
                            </option>

                            <option
                                value="Cheque"
                                {{ request('payment_method') === 'Cheque'
                                    ? 'selected'
                                    : '' }}
                            >
                                Cheque
                            </option>

                            <option
                                value="Online"
                                {{ request('payment_method') === 'Online'
                                    ? 'selected'
                                    : '' }}
                            >
                                Online
                            </option>

                        </select>

                    </div>


                    <div class="col-md-2 mb-2">

                        <select
                            name="busy_sync_status"
                            class="form-control"
                        >

                            <option value="">
                                BUSY Status
                            </option>

                            <option
                                value="synced"
                                {{ request('busy_sync_status') === 'synced'
                                    ? 'selected'
                                    : '' }}
                            >
                                Synced
                            </option>

                            <option
                                value="pending"
                                {{ request('busy_sync_status') === 'pending'
                                    ? 'selected'
                                    : '' }}
                            >
                                Pending
                            </option>

                            <option
                                value="failed"
                                {{ request('busy_sync_status') === 'failed'
                                    ? 'selected'
                                    : '' }}
                            >
                                Failed
                            </option>

                        </select>

                    </div>


                    <div class="col-md-2 mb-2">

                        <input
                            type="date"
                            name="from_date"
                            value="{{ request('from_date') }}"
                            class="form-control"
                        >

                    </div>


                    <div class="col-md-2 mb-2">

                        <input
                            type="date"
                            name="to_date"
                            value="{{ request('to_date') }}"
                            class="form-control"
                        >

                    </div>


                    <div class="col-md-1 mb-2">

                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                        >
                            <i class="fas fa-search"></i>
                        </button>

                    </div>

                </div>

            </form>

        </div>


        {{-- ================================================= --}}
        {{-- FLASH MESSAGES --}}
        {{-- ================================================= --}}

        <div class="card-body pb-0">

            @if(session('success'))

                <div class="alert alert-success">
                    {{ session('success') }}
                </div>

            @endif

            @if(session('warning'))

                <div class="alert alert-warning">
                    {{ session('warning') }}
                </div>

            @endif

            @if(session('error'))

                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>

            @endif

        </div>


        {{-- ================================================= --}}
        {{-- TABLE --}}
        {{-- ================================================= --}}

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered table-hover align-middle">

                    <thead>

                        <tr>

                            <th>
                                #
                            </th>

                            <th>
                                Party Name
                            </th>

                            <th>
                                Amount
                            </th>

                            <th>
                                Received Date
                            </th>

                            <th>
                                Mode
                            </th>

                            <th>
                                Collection Type
                            </th>

                            <th>
                                BUSY
                            </th>

                            <th>
                                Actions
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($collections as $collection)

                            @php

                                $partyName =
                                    $collection->client?->company_name
                                    ?: $collection->client?->name
                                    ?: 'N/A';

                            @endphp

                            <tr>

                                <td>

                                    {{ $collections->firstItem() + $loop->index }}

                                </td>


                                <td>

                                    <strong>
                                        {{ $partyName }}
                                    </strong>

                                    @if(
                                        $collection->client?->busyparty_id
                                    )

                                        <div class="small text-muted">

                                            BUSY Party:
                                            {{ $collection->client->busyparty_id }}

                                        </div>

                                    @endif

                                </td>


                                <td>

                                    <strong>
                                        Rs.
                                        {{ number_format(
                                            (float) $collection->payment_received,
                                            2
                                        ) }}
                                    </strong>

                                </td>


                                <td>

                                    {{ $collection->payment_date
                                        ? $collection->payment_date->format('d M Y')
                                        : '-' }}

                                </td>


                                <td>

                                    <span class="badge bg-info">

                                        {{ $collection->payment_method ?: '-' }}

                                    </span>

                                </td>


                                <td>

                                    {{ $collection->collectionType?->name ?: '-' }}

                                </td>


                                <td>

                                    @if(
                                        $collection->busy_sync_status === 'synced'
                                    )

                                        <span class="badge bg-success">
                                            Synced
                                        </span>

                                        @if(
                                            $collection->busycollection_id
                                        )

                                            <div class="small text-muted mt-1">

                                                Vch:
                                                {{ $collection->busycollection_id }}

                                            </div>

                                        @endif

                                    @elseif(
                                        $collection->busy_sync_status === 'failed'
                                    )

                                        <span class="badge bg-danger">
                                            Failed
                                        </span>

                                    @else

                                        <span class="badge bg-warning text-dark">
                                            Pending
                                        </span>

                                    @endif

                                </td>


                                <td>

                                    <div class="d-flex gap-1">

                                        {{-- VIEW --}}

                                        <a
                                            href="{{ route(
                                                'collections.show',
                                                $collection
                                            ) }}"
                                            class="btn btn-sm btn-info"
                                            title="View"
                                        >

                                            <i class="fas fa-eye"></i>

                                        </a>


                                        {{-- EDIT --}}

                                        <a
                                            href="{{ route(
                                                'collections.edit',
                                                $collection
                                            ) }}"
                                            class="btn btn-sm btn-warning"
                                            title="Edit"
                                        >

                                            <i class="fas fa-edit"></i>

                                        </a>


                                        {{-- SYNC --}}

                                        @if(
                                            $collection->busy_sync_status !== 'synced'
                                        )

                                            <form
                                                action="{{ route(
                                                    'collections.sync-busy',
                                                    $collection
                                                ) }}"
                                                method="POST"
                                            >

                                                @csrf

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-primary"
                                                    title="Sync with BUSY"
                                                >

                                                    <i class="fas fa-sync"></i>

                                                </button>

                                            </form>

                                        @endif


                                        {{-- DELETE --}}

                                        <form
                                            action="{{ route(
                                                'collections.destroy',
                                                $collection
                                            ) }}"
                                            method="POST"
                                            onsubmit="
                                                return confirm(
                                                    'Are you sure you want to delete this collection?'
                                                );
                                            "
                                        >

                                            @csrf

                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-danger"
                                                title="Delete"
                                            >

                                                <i class="fas fa-trash"></i>

                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="8"
                                    class="text-center py-5"
                                >

                                    No collections found.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            <div class="mt-3">

                {{ $collections->links() }}

            </div>

        </div>

    </div>

</div>

@endsection