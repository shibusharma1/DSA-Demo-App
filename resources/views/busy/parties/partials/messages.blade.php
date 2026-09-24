@if(session('success'))

    <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4">

        <div class="flex items-start gap-3">

            <div class="text-emerald-600">
                ✓
            </div>

            <div>
                <p class="font-semibold text-emerald-800">
                    Success
                </p>

                <p class="mt-1 text-sm text-emerald-700">
                    {{ session('success') }}
                </p>
            </div>

        </div>

    </div>

@endif


@if(session('error'))

    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4">

        <div class="flex items-start gap-3">

            <div class="text-red-600">
                ✕
            </div>

            <div>
                <p class="font-semibold text-red-800">
                    BUSY Synchronization Failed
                </p>

                <p class="mt-1 text-sm text-red-700">
                    {{ session('error') }}
                </p>
            </div>

        </div>

    </div>

@endif


@if($errors->any())

    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4">

        <p class="font-semibold text-red-800">
            Please correct the following errors:
        </p>

        <ul class="mt-2 list-disc pl-5 text-sm text-red-700">

            @foreach($errors->all() as $error)

                <li>
                    {{ $error }}
                </li>

            @endforeach

        </ul>

    </div>

@endif