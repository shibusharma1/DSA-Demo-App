@extends('layout.app')

@section('content')

<div class="min-h-screen bg-slate-50 py-6">

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="mb-6 flex items-center justify-between">

            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    Create Party
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Create a customer and automatically save it to BUSY.
                </p>
            </div>

            <a
                href="{{ route('busy.parties.index') }}"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            >
                ← Back
            </a>

        </div>

        {{-- Messages --}}
        @include('busy.parties.partials.messages')

        <form
            method="POST"
            action="{{ route('busy.parties.store') }}"
        >

            @csrf

            @include('busy.parties.partials.form', [
                'party' => null,
                'countries' => $countries,
            ])

            <div class="mt-6 flex justify-end gap-3">

                <a
                    href="{{ route('busy.parties.index') }}"
                    class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="rounded-lg bg-teal-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-teal-800"
                >
                    Create Customer & Save to BUSY
                </button>

            </div>

        </form>

    </div>

</div>

@endsection