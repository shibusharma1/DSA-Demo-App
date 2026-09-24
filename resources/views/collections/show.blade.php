@extends('layout.app')

@section('content')

<div class="container-fluid">

    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">

            <h4 class="mb-0">
                Collection Details
            </h4>

            <div class="d-flex gap-2">

                <a
                    href="{{ route(
                        'collections.edit',
                        $collection
                    ) }}"
                    class="btn btn-warning"
                >
                    <i class="fas fa-edit"></i>
                    Edit
                </a>

                <a
                    href="{{ route(
                        'collections.index'
                    ) }}"
                    class="btn btn-outline-info"
                >
                    <i class="fas fa-arrow-left"></i>
                    Back
                </a>

            </div>

        </div>


        <div class="card-body">

            {{-- ============================================= --}}
            {{-- MAIN INFORMATION --}}
            {{-- ============================================= --}}

            <div class="row">

                <div class="col-md-6 mb-4">

                    <label class="text-muted">
                        Party Name
                    </label>

                    <h5>

                        {{ $collection->client?->company_name
                            ?: $collection->client?->name
                            ?: 'N/A' }}

                    </h5>

                </div>


                <div class="col-md-6 mb-4">

                    <label class="text-muted">
                        Received Date
                    </label>

                    <h5>

                        {{ $collection->payment_date
                            ? $collection->payment_date->format('d M Y')
                            : '-' }}

                    </h5>

                </div>


                <div class="col-md-6 mb-4">

                    <label class="text-muted">
                        Amount
                    </label>

                    <h5>

                        Rs.
                        {{ number_format(
                            (float)
                            $collection->payment_received,
                            2
                        ) }}

                    </h5>

                </div>


                <div class="col-md-6 mb-4">

                    <label class="text-muted">
                        Mode
                    </label>

                    <h5>
                        {{ $collection->payment_method ?: '-' }}
                    </h5>

                </div>


                <div class="col-md-6 mb-4">

                    <label class="text-muted">
                        Collection Type
                    </label>

                    <h5>
                        {{ $collection->collectionType?->name ?: '-' }}
                    </h5>

                </div>


                <div class="col-md-6 mb-4">

                    <label class="text-muted">
                        BUSY Party ID
                    </label>

                    <h5>
                        {{ $collection->client?->busyparty_id ?: '-' }}
                    </h5>

                </div>

            </div>


            {{-- ============================================= --}}
            {{-- NOTES --}}
            {{-- ============================================= --}}

            <div class="mb-4">

                <label class="text-muted">
                    Notes
                </label>

                <div class="border rounded p-3">

                    {!! nl2br(
                        e(
                            $collection->payment_note
                            ?: 'No notes'
                        )
                    ) !!}

                </div>

            </div>


            {{-- ============================================= --}}
            {{-- CHEQUE --}}
            {{-- ============================================= --}}

            @if(
                $collection->cheque_no ||
                $collection->cheque_date
            )

                <div class="card bg-light mb-4">

                    <div class="card-body">

                        <h6 class="fw-bold">
                            Cheque Information
                        </h6>

                        <div class="row">

                            <div class="col-md-6">

                                <strong>
                                    Cheque No:
                                </strong>

                                {{ $collection->cheque_no ?: '-' }}

                            </div>

                            <div class="col-md-6">

                                <strong>
                                    Cheque Date:
                                </strong>

                                {{ $collection->cheque_date
                                    ? $collection->cheque_date->format('d M Y')
                                    : '-' }}

                            </div>

                        </div>

                    </div>

                </div>

            @endif


            {{-- ============================================= --}}
            {{-- IMAGES --}}
            {{-- ============================================= --}}

            @if($collection->images->count())

                <div class="mb-4">

                    <h6 class="fw-bold">
                        Images
                    </h6>

                    <div class="d-flex flex-wrap gap-3">

                        @foreach($collection->images as $image)

                            <a
                                href="{{ asset(
                                    'storage/' .
                                    $image->image_path
                                ) }}"
                                target="_blank"
                            >

                                <img
                                    src="{{ asset(
                                        'storage/' .
                                        $image->image_path
                                    ) }}"
                                    class="rounded border"
                                    style="
                                        width:180px;
                                        height:180px;
                                        object-fit:cover;
                                    "
                                >

                            </a>

                        @endforeach

                    </div>

                </div>

            @endif


            {{-- ============================================= --}}
            {{-- BUSY INFORMATION --}}
            {{-- ============================================= --}}

            <div class="card">

                <div class="card-header">

                    <strong>
                        BUSY Synchronization
                    </strong>

                </div>

                <div class="card-body">

                    <div class="row">

                        <div class="col-md-4">

                            <strong>
                                Status
                            </strong>

                            <div class="mt-1">

                                @if(
                                    $collection->busy_sync_status
                                    === 'synced'
                                )

                                    <span class="badge bg-success">
                                        Synced
                                    </span>

                                @elseif(
                                    $collection->busy_sync_status
                                    === 'failed'
                                )

                                    <span class="badge bg-danger">
                                        Failed
                                    </span>

                                @else

                                    <span class="badge bg-warning text-dark">
                                        Pending
                                    </span>

                                @endif

                            </div>

                        </div>


                        <div class="col-md-4">

                            <strong>
                                BUSY Voucher Code
                            </strong>

                            <div class="mt-1">

                                {{ $collection->busycollection_id ?: '-' }}

                            </div>

                        </div>


                        <div class="col-md-4">

                            <strong>
                                Last Synced
                            </strong>

                            <div class="mt-1">

                                {{ $collection->busy_synced_at
                                    ? $collection->busy_synced_at->format(
                                        'd M Y h:i A'
                                    )
                                    : '-' }}

                            </div>

                        </div>

                    </div>


                    @if(
                        $collection->busy_sync_message
                    )

                        <hr>

                        <strong>
                            BUSY Message
                        </strong>

                        <p class="mb-0 text-muted">

                            {{ $collection->busy_sync_message }}

                        </p>

                    @endif

                </div>

            </div>


            {{-- ============================================= --}}
            {{-- MANUAL SYNC --}}
            {{-- ============================================= --}}

            @if(
                $collection->busy_sync_status !== 'synced'
            )

                <form
                    action="{{ route(
                        'collections.sync-busy',
                        $collection
                    ) }}"
                    method="POST"
                    class="mt-3"
                >

                    @csrf

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <i class="fas fa-sync"></i>

                        Sync with BUSY

                    </button>

                </form>

            @endif

        </div>

    </div>

</div>

@endsection