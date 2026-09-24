@extends('layout.app')

@section('content')

<div class="container-fluid">

    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">

            <h4 class="mb-0">
                Collections
            </h4>

            <a
                href="{{ route('collections.index') }}"
                class="btn btn-outline-info"
            >
                <i class="fas fa-arrow-left"></i>
                Back
            </a>

        </div>

        <div class="card-body">

            @include(
                'collections.partials.form',
                [
                    'action' =>
                        route('collections.store'),

                    'method' => 'POST',

                    'collection' => null,

                    'clients' => $clients,

                    'collectionTypes' =>
                        $collectionTypes,

                    'banks' => $banks,
                ]
            )

        </div>

    </div>

</div>

@endsection