@php

    $isEdit =
        !empty($collection);

    $selectedClient =
        old(
            'client_id',
            $collection->client_id ?? ''
        );

    $paymentMethod =
        old(
            'payment_method',
            $collection->payment_method
                ?? 'Cash'
        );

    $paymentDate =
        old(
            'payment_date',
            isset($collection->payment_date)
                ? $collection->payment_date
                    ->format('Y-m-d')
                : now()->format('Y-m-d')
        );

@endphp


@if($errors->any())

    <div class="alert alert-danger">

        <strong>
            Please fix the following errors:
        </strong>

        <ul class="mb-0 mt-2">

            @foreach($errors->all() as $error)

                <li>
                    {{ $error }}
                </li>

            @endforeach

        </ul>

    </div>

@endif


<form
    action="{{ $action }}"
    method="POST"
    enctype="multipart/form-data"
>

    @csrf

    @if($method !== 'POST')
        @method($method)
    @endif


    {{-- ===================================================== --}}
    {{-- BASIC COLLECTION INFORMATION --}}
    {{-- ===================================================== --}}

    <div class="row">

        {{-- PARTY NAME --}}

        <div class="col-md-6 mb-4">

            <label class="form-label fw-bold">

                Party Name
                <span class="text-danger">*</span>

            </label>

            <select
                name="client_id"
                id="client_id"
                class="form-control select2 @error('client_id') is-invalid @enderror"
                required
            >

                <option value="">
                    Select Party
                </option>

                @foreach($clients as $client)

                    @php

                        $partyName =
                            $client->company_name
                            ?: $client->name
                            ?: 'Unnamed Party';

                    @endphp

                    <option
                        value="{{ $client->id }}"
                        {{ (string) $selectedClient === (string) $client->id ? 'selected' : '' }}
                    >

                        {{ $partyName }}

                        @if($client->busyparty_id)

                            — BUSY:
                            {{ $client->busyparty_id }}

                        @endif

                    </option>

                @endforeach

            </select>

            @error('client_id')

                <div class="invalid-feedback">
                    {{ $message }}
                </div>

            @enderror

        </div>


        {{-- RECEIVED DATE --}}

        <div class="col-md-6 mb-4">

            <label class="form-label fw-bold">

                Received Date
                <span class="text-danger">*</span>

            </label>

            <div class="input-group">

                <span class="input-group-text">
                    <i class="far fa-calendar-alt"></i>
                </span>

                <input
                    type="date"
                    name="payment_date"
                    value="{{ $paymentDate }}"
                    class="form-control @error('payment_date') is-invalid @enderror"
                    required
                >

            </div>

            @error('payment_date')

                <div class="text-danger small">
                    {{ $message }}
                </div>

            @enderror

        </div>

    </div>


    <div class="row">

        {{-- AMOUNT --}}

        <div class="col-md-6 mb-4">

            <label class="form-label fw-bold">

                Amount
                <span class="text-danger">*</span>

            </label>

            <div class="input-group">

                <span class="input-group-text">
                    Rs
                </span>

                <input
                    type="number"
                    name="payment_received"
                    value="{{ old('payment_received', $collection->payment_received ?? '') }}"
                    class="form-control @error('payment_received') is-invalid @enderror"
                    placeholder="Amount Received"
                    min="0.01"
                    step="0.01"
                    required
                >

            </div>

            @error('payment_received')

                <div class="text-danger small">
                    {{ $message }}
                </div>

            @enderror

        </div>


        {{-- MODE --}}

        <div class="col-md-6 mb-4">

            <label class="form-label fw-bold">
                Mode
            </label>

            <select
                name="payment_method"
                id="payment_method"
                class="form-control"
            >

                <option
                    value="Cash"
                    {{ $paymentMethod === 'Cash' ? 'selected' : '' }}
                >
                    Cash
                </option>

                <option
                    value="Bank"
                    {{ $paymentMethod === 'Bank' ? 'selected' : '' }}
                >
                    Bank
                </option>

                <option
                    value="Cheque"
                    {{ $paymentMethod === 'Cheque' ? 'selected' : '' }}
                >
                    Cheque
                </option>

                <option
                    value="Online"
                    {{ $paymentMethod === 'Online' ? 'selected' : '' }}
                >
                    Online
                </option>

                <option
                    value="Other"
                    {{ $paymentMethod === 'Other' ? 'selected' : '' }}
                >
                    Other
                </option>

            </select>

        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- NOTES + IMAGE --}}
    {{-- ===================================================== --}}

    <div class="row">

        {{-- NOTES --}}

        <div class="col-md-6 mb-4">

            <label class="form-label fw-bold">
                Notes
            </label>

            <textarea
                name="payment_note"
                class="form-control"
                rows="10"
                placeholder="Notes"
            >{{ old('payment_note', $collection->payment_note ?? '') }}</textarea>

        </div>


        {{-- IMAGE UPLOAD --}}

        <div class="col-md-6 mb-4">

            <label class="form-label fw-bold">

                Image

                <small class="text-muted">
                    *max 10 images
                </small>

            </label>

            <div
                id="image-preview-container"
                class="d-flex flex-wrap gap-2 mb-3"
            >

                @if($isEdit && $collection->images->count())

                    @foreach($collection->images as $image)

                        <div
                            class="collection-image-preview position-relative"
                            data-existing-image="{{ $image->id }}"
                        >

                            <img
                                src="{{ asset('storage/' . $image->image_path) }}"
                                alt="Collection Image"
                                class="rounded border"
                                style="
                                    width:150px;
                                    height:150px;
                                    object-fit:cover;
                                "
                            >

                            <button
                                type="button"
                                class="btn btn-danger btn-sm remove-existing-image"
                                data-id="{{ $image->id }}"
                                style="
                                    position:absolute;
                                    top:5px;
                                    right:5px;
                                    width:30px;
                                    height:30px;
                                    border-radius:50%;
                                    padding:0;
                                "
                            >
                                ×
                            </button>

                        </div>

                    @endforeach

                @endif

            </div>


            <div class="d-flex align-items-start">

                <div>

                    <input
                        type="file"
                        name="images[]"
                        id="images"
                        class="d-none"
                        accept="image/jpeg,image/png,image/webp"
                        multiple
                    >

                    <label
                        for="images"
                        class="btn btn-primary"
                    >
                        <i class="fas fa-plus"></i>
                    </label>

                </div>

            </div>


            <small class="text-muted d-block mt-2">
                JPG, JPEG, PNG or WEBP. Maximum 5 MB each.
            </small>

            <div
                id="selected-image-count"
                class="small text-muted mt-1"
            >
                0 new images selected
            </div>

            @error('images')

                <div class="text-danger small">
                    {{ $message }}
                </div>

            @enderror

            @error('images.*')

                <div class="text-danger small">
                    {{ $message }}
                </div>

            @enderror

        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- BANK --}}
    {{-- ===================================================== --}}

    <div class="row">

        <div class="col-md-6 mb-4">

            <label class="form-label fw-bold">
                Collection Type
            </label>

            <select
                name="collection_types_id"
                class="form-control"
            >

                <option value="">
                    Select Collection Type
                </option>

                @foreach($collectionTypes as $type)

                    <option
                        value="{{ $type->id }}"
                        {{ old(
                            'collection_types_id',
                            $collection->collection_types_id ?? ''
                        ) == $type->id ? 'selected' : '' }}
                    >
                        {{ $type->name }}
                    </option>

                @endforeach

            </select>

        </div>


        <div
            class="col-md-6 mb-4"
            id="bank-wrapper"
            style="
                display:
                {{ in_array(
                    strtolower($paymentMethod),
                    ['bank', 'cheque', 'online']
                ) ? 'block' : 'none' }};
            "
        >

            <label class="form-label fw-bold">
                Bank
            </label>

            <select
                name="bank_id"
                class="form-control"
            >

                <option value="">
                    Select Bank
                </option>

                @foreach($banks as $bank)

                    <option
                        value="{{ $bank->id }}"
                        {{ old(
                            'bank_id',
                            $collection->bank_id ?? ''
                        ) == $bank->id ? 'selected' : '' }}
                    >
                        {{ $bank->name }}
                    </option>

                @endforeach

            </select>

        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- CHEQUE --}}
    {{-- ===================================================== --}}

    <div
        class="row"
        id="cheque-wrapper"
        style="
            display:
            {{ strtolower($paymentMethod) === 'cheque'
                ? 'flex'
                : 'none' }};
        "
    >

        <div class="col-md-6 mb-4">

            <label class="form-label fw-bold">
                Cheque No.
            </label>

            <input
                type="text"
                name="cheque_no"
                class="form-control"
                value="{{ old(
                    'cheque_no',
                    $collection->cheque_no ?? ''
                ) }}"
                placeholder="Cheque Number"
            >

        </div>


        <div class="col-md-6 mb-4">

            <label class="form-label fw-bold">
                Cheque Date
            </label>

            <input
                type="date"
                name="cheque_date"
                class="form-control"
                value="{{ old(
                    'cheque_date',
                    isset($collection->cheque_date)
                        ? $collection->cheque_date->format('Y-m-d')
                        : ''
                ) }}"
            >

        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- ADDITIONAL ACCOUNT INFORMATION --}}
    {{-- ===================================================== --}}

    <div class="row">

        <div class="col-md-4 mb-4">

            <label class="form-label">
                Due Payment
            </label>

            <input
                type="number"
                name="due_payment"
                class="form-control"
                min="0"
                step="0.01"
                value="{{ old(
                    'due_payment',
                    $collection->due_payment ?? '0.00'
                ) }}"
            >

        </div>


        <div class="col-md-4 mb-4">

            <label class="form-label">
                Next Date
            </label>

            <input
                type="date"
                name="next_date"
                class="form-control"
                value="{{ old(
                    'next_date',
                    isset($collection->next_date)
                        ? $collection->next_date->format('Y-m-d')
                        : ''
                ) }}"
            >

        </div>


        <div class="col-md-4 mb-4">

            <label class="form-label">
                Payment Status
            </label>

            <select
                name="payment_status"
                class="form-control"
            >

                <option
                    value="Received"
                    {{ old(
                        'payment_status',
                        $collection->payment_status ?? 'Received'
                    ) === 'Received'
                        ? 'selected'
                        : '' }}
                >
                    Received
                </option>

                <option
                    value="Pending"
                    {{ old(
                        'payment_status',
                        $collection->payment_status ?? ''
                    ) === 'Pending'
                        ? 'selected'
                        : '' }}
                >
                    Pending
                </option>

                <option
                    value="Cancelled"
                    {{ old(
                        'payment_status',
                        $collection->payment_status ?? ''
                    ) === 'Cancelled'
                        ? 'selected'
                        : '' }}
                >
                    Cancelled
                </option>

            </select>

        </div>

    </div>


    <div class="row">

        <div class="col-md-6 mb-4">

            <label class="form-label">
                Payment Status Note
            </label>

            <textarea
                name="payment_status_note"
                class="form-control"
                rows="3"
                placeholder="Payment status note"
            >{{ old(
                'payment_status_note',
                $collection->payment_status_note ?? ''
            ) }}</textarea>

        </div>


        <div class="col-md-6 mb-4">

            <label class="form-label">
                Status
            </label>

            <select
                name="status"
                class="form-control"
            >

                <option
                    value="Pending"
                    {{ old(
                        'status',
                        $collection->status ?? 'Pending'
                    ) === 'Pending'
                        ? 'selected'
                        : '' }}
                >
                    Pending
                </option>

                <option
                    value="Approved"
                    {{ old(
                        'status',
                        $collection->status ?? ''
                    ) === 'Approved'
                        ? 'selected'
                        : '' }}
                >
                    Approved
                </option>

                <option
                    value="Cancelled"
                    {{ old(
                        'status',
                        $collection->status ?? ''
                    ) === 'Cancelled'
                        ? 'selected'
                        : '' }}
                >
                    Cancelled
                </option>

            </select>

        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- INCLUDE IN CREDIT --}}
    {{-- ===================================================== --}}

    <div class="mb-4">

        <div class="form-check">

            <input
                type="checkbox"
                name="include_in_credit"
                value="1"
                class="form-check-input"
                id="include_in_credit"
                {{ old(
                    'include_in_credit',
                    $collection->include_in_credit ?? false
                ) ? 'checked' : '' }}
            >

            <label
                class="form-check-label"
                for="include_in_credit"
            >
                Include in Credit
            </label>

        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- EXISTING IMAGE DELETE INPUTS --}}
    {{-- ===================================================== --}}

    <div id="deleted-images-container"></div>


    {{-- ===================================================== --}}
    {{-- BUSY STATUS --}}
    {{-- ===================================================== --}}

    @if($isEdit)

        <div class="card bg-light mb-4">

            <div class="card-body">

                <h6 class="fw-bold">
                    BUSY Synchronization
                </h6>

                <div class="row">

                    <div class="col-md-4">

                        <strong>
                            Status:
                        </strong>

                        @if($collection->busy_sync_status === 'synced')

                            <span class="badge bg-success">
                                Synced
                            </span>

                        @elseif($collection->busy_sync_status === 'failed')

                            <span class="badge bg-danger">
                                Failed
                            </span>

                        @else

                            <span class="badge bg-warning text-dark">
                                Pending
                            </span>

                        @endif

                    </div>


                    <div class="col-md-4">

                        <strong>
                            BUSY Voucher Code:
                        </strong>

                        {{ $collection->busycollection_id ?: '—' }}

                    </div>


                    <div class="col-md-4">

                        <strong>
                            Last Sync:
                        </strong>

                        {{ $collection->busy_synced_at
                            ? $collection->busy_synced_at->format('d M Y h:i A')
                            : '—' }}

                    </div>

                </div>

                @if($collection->busy_sync_message)

                    <div class="mt-3">

                        <strong>
                            Message:
                        </strong>

                        <div class="text-muted">
                            {{ $collection->busy_sync_message }}
                        </div>

                    </div>

                @endif

            </div>

        </div>

    @endif


    {{-- ===================================================== --}}
    {{-- BUTTONS --}}
    {{-- ===================================================== --}}

    <div class="d-flex justify-content-end gap-2">

        <a
            href="{{ route('collections.index') }}"
            class="btn btn-secondary"
        >
            Cancel
        </a>

        <button
            type="submit"
            class="btn btn-primary"
        >

            @if($isEdit)

                <i class="fas fa-save"></i>
                Update

            @else

                <i class="fas fa-save"></i>
                Create

            @endif

        </button>

    </div>

</form>


@push('scripts')

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const paymentMethod =
            document.getElementById(
                'payment_method'
            );

        const bankWrapper =
            document.getElementById(
                'bank-wrapper'
            );

        const chequeWrapper =
            document.getElementById(
                'cheque-wrapper'
            );

        function updatePaymentFields() {

            const value =
                paymentMethod.value.toLowerCase();

            /*
             * Bank required for Bank,
             * Cheque and Online.
             */
            if (
                value === 'bank' ||
                value === 'cheque' ||
                value === 'online'
            ) {

                bankWrapper.style.display =
                    'block';

            } else {

                bankWrapper.style.display =
                    'none';
            }

            /*
             * Cheque fields.
             */
            if (value === 'cheque') {

                chequeWrapper.style.display =
                    'flex';

            } else {

                chequeWrapper.style.display =
                    'none';
            }
        }

        paymentMethod.addEventListener(
            'change',
            updatePaymentFields
        );

        updatePaymentFields();


        /*
         * IMAGE PREVIEW
         */
        const imageInput =
            document.getElementById(
                'images'
            );

        const previewContainer =
            document.getElementById(
                'image-preview-container'
            );

        const countText =
            document.getElementById(
                'selected-image-count'
            );

        let selectedFiles = [];


        imageInput.addEventListener(
            'change',
            function () {

                const existingImages =
                    document.querySelectorAll(
                        '[data-existing-image]'
                    ).length;

                const total =
                    existingImages +
                    this.files.length;

                if (total > 10) {

                    alert(
                        'Maximum 10 images are allowed.'
                    );

                    this.value = '';

                    return;
                }

                selectedFiles =
                    Array.from(
                        this.files
                    );

                countText.textContent =
                    selectedFiles.length +
                    ' new images selected';

                selectedFiles.forEach(
                    function (file) {

                        const reader =
                            new FileReader();

                        reader.onload =
                            function (event) {

                                const wrapper =
                                    document.createElement(
                                        'div'
                                    );

                                wrapper.className =
                                    'collection-image-preview position-relative';

                                wrapper.style.width =
                                    '150px';

                                wrapper.style.height =
                                    '150px';

                                const img =
                                    document.createElement(
                                        'img'
                                    );

                                img.src =
                                    event.target.result;

                                img.style.width =
                                    '150px';

                                img.style.height =
                                    '150px';

                                img.style.objectFit =
                                    'cover';

                                img.className =
                                    'rounded border';

                                wrapper.appendChild(
                                    img
                                );

                                previewContainer.appendChild(
                                    wrapper
                                );
                            };

                        reader.readAsDataURL(
                            file
                        );
                    }
                );
            }
        );


        /*
         * REMOVE EXISTING IMAGE
         */
        document
            .querySelectorAll(
                '.remove-existing-image'
            )
            .forEach(
                function (button) {

                    button.addEventListener(
                        'click',
                        function () {

                            const id =
                                this.dataset.id;

                            const wrapper =
                                document.querySelector(
                                    '[data-existing-image="' +
                                    id +
                                    '"]'
                                );

                            if (wrapper) {

                                wrapper.remove();
                            }

                            const input =
                                document.createElement(
                                    'input'
                                );

                            input.type =
                                'hidden';

                            input.name =
                                'delete_images[]';

                            input.value =
                                id;

                            document
                                .getElementById(
                                    'deleted-images-container'
                                )
                                .appendChild(
                                    input
                                );
                        }
                    );
                }
            );

    });

</script>

@endpush