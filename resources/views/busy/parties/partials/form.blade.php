@php
    $party = $party ?? null;

    $value = function ($field, $default = '') use ($party) {
        return old(
            $field,
            $party?->{$field} ?? $default
        );
    };
@endphp

{{-- Basic Information --}}
<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 bg-teal-700 px-5 py-3">
            <h2 class="font-semibold text-white">
                Basic Information
            </h2>
        </div>

        <div class="space-y-5 p-5">

            <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                    Party Name *
                </label>

                <input
                    type="text"
                    name="company_name"
                    value="{{ $value('company_name') }}"
                    required
                    placeholder="Party Name"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-teal-600 focus:ring-2 focus:ring-teal-100"
                >

                @error('company_name')
                    <p class="mt-1 text-xs text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                    Party Alias / Code
                </label>

                <input
                    type="text"
                    name="alias"
                    value="{{ $value('alias') }}"
                    placeholder="Alias / Party Code"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"
                >
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                    Contact Person Name
                </label>

                <input
                    type="text"
                    name="name"
                    value="{{ $value('name') }}"
                    placeholder="Contact Person Name"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"
                >
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                    Print Name
                </label>

                <input
                    type="text"
                    name="print_name"
                    value="{{ $value('print_name') }}"
                    placeholder="Print Name"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"
                >
            </div>

        </div>

    </div>

    {{-- BUSY Account --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 bg-teal-700 px-5 py-3">

            <h2 class="font-semibold text-white">
                BUSY Account Details
            </h2>

        </div>

        <div class="space-y-5 p-5">

            <div>

                <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                    Parent Group *
                </label>

                <input
                    type="text"
                    name="parent_group"
                    value="{{ $value('parent_group', 'Sundry Debtors') }}"
                    required
                    placeholder="Sundry Debtors"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"
                >

                <p class="mt-1 text-xs text-slate-500">
                    For normal customers, BUSY commonly uses Sundry Debtors.
                </p>

            </div>

            <div>

                <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                    Supplier Type
                </label>

                <input
                    type="text"
                    name="supplier_type"
                    value="{{ $value('supplier_type') }}"
                    placeholder="1"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"
                >

            </div>

            <div class="flex items-center gap-3">

                <input
                    type="hidden"
                    name="bill_by_bill_balancing"
                    value="0"
                >

                <input
                    type="checkbox"
                    name="bill_by_bill_balancing"
                    value="1"
                    @checked($value('bill_by_bill_balancing'))
                    class="h-4 w-4 rounded border-slate-300 text-teal-700"
                >

                <label class="text-sm font-medium text-slate-700">
                    Bill By Bill Balancing
                </label>

            </div>

            <div>

                <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                    Status
                </label>

                <select
                    name="status"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"
                >

                    <option
                        value="Active"
                        @selected($value('status', 'Active') === 'Active')
                    >
                        Active
                    </option>

                    <option
                        value="Inactive"
                        @selected($value('status') === 'Inactive')
                    >
                        Inactive
                    </option>

                </select>

            </div>

        </div>

    </div>

</div>


{{-- Contact Details --}}
<div class="mt-6 rounded-xl border border-slate-200 bg-white shadow-sm">

    <div class="border-b border-slate-200 bg-teal-700 px-5 py-3">

        <h2 class="font-semibold text-white">
            Contact Details
        </h2>

    </div>

    <div class="grid grid-cols-1 gap-5 p-5 md:grid-cols-2 lg:grid-cols-3">

        <div>
            <label class="label">Phone</label>
            <input
                type="text"
                name="phone"
                value="{{ $value('phone') }}"
                class="input"
                placeholder="Phone"
            >
        </div>

        <div>
            <label class="label">Mobile</label>
            <input
                type="text"
                name="mobile"
                value="{{ $value('mobile') }}"
                class="input"
                placeholder="Mobile"
            >
        </div>

        <div>
            <label class="label">WhatsApp No.</label>
            <input
                type="text"
                name="whatsapp_no"
                value="{{ $value('whatsapp_no') }}"
                class="input"
                placeholder="WhatsApp Number"
            >
        </div>

        <div>
            <label class="label">Fax</label>
            <input
                type="text"
                name="fax"
                value="{{ $value('fax') }}"
                class="input"
                placeholder="Fax"
            >
        </div>

        <div>
            <label class="label">Email</label>
            <input
                type="email"
                name="email"
                value="{{ $value('email') }}"
                class="input"
                placeholder="Email"
            >
        </div>

        <div>
            <label class="label">Department</label>
            <input
                type="text"
                name="cont_dept_name"
                value="{{ $value('cont_dept_name') }}"
                class="input"
                placeholder="Department"
            >
        </div>

    </div>

</div>


{{-- Address --}}
<div class="mt-6 rounded-xl border border-slate-200 bg-white shadow-sm">

    <div class="border-b border-slate-200 bg-teal-700 px-5 py-3">

        <h2 class="font-semibold text-white">
            Location Details
        </h2>

    </div>

    <div class="grid grid-cols-1 gap-5 p-5 md:grid-cols-2">

        <div>
            <label class="label">Country</label>

            <select
                name="country"
                class="input"
            >

                <option value="">
                    Select Country
                </option>

                @foreach($countries as $country)

                    <option
                        value="{{ $country->id }}"
                        @selected(
                            (string) $value('country') ===
                            (string) $country->id
                        )
                    >
                        {{ $country->name }}
                    </option>

                @endforeach

            </select>

        </div>

        <div>
            <label class="label">Phone Code</label>

            <input
                type="text"
                name="phonecode"
                value="{{ $value('phonecode') }}"
                class="input"
                placeholder="+977"
            >

        </div>

        <div>
            <label class="label">State</label>

            <input
                type="text"
                name="state"
                value="{{ $value('state') }}"
                class="input"
                placeholder="State"
            >

        </div>

        <div>
            <label class="label">City</label>

            <input
                type="text"
                name="city"
                value="{{ $value('city') }}"
                class="input"
                placeholder="City"
            >

        </div>

        <div>
            <label class="label">Area</label>

            <input
                type="text"
                name="area"
                value="{{ $value('area') }}"
                class="input"
                placeholder="Area"
            >

        </div>

        <div>
            <label class="label">Address Line 1</label>

            <textarea
                name="address_1"
                class="input"
                rows="2"
                placeholder="Address Line 1"
            >{{ $value('address_1') }}</textarea>

        </div>

        <div>
            <label class="label">Address Line 2</label>

            <textarea
                name="address_2"
                class="input"
                rows="2"
                placeholder="Address Line 2"
            >{{ $value('address_2') }}</textarea>

        </div>

        <div>
            <label class="label">Address Line 3</label>

            <textarea
                name="address_3"
                class="input"
                rows="2"
                placeholder="Address Line 3"
            >{{ $value('address_3') }}</textarea>

        </div>

        <div>
            <label class="label">Address Line 4</label>

            <textarea
                name="address_4"
                class="input"
                rows="2"
                placeholder="Address Line 4"
            >{{ $value('address_4') }}</textarea>

        </div>

    </div>

</div>


{{-- Tax --}}
<div class="mt-6 rounded-xl border border-slate-200 bg-white shadow-sm">

    <div class="border-b border-slate-200 bg-teal-700 px-5 py-3">

        <h2 class="font-semibold text-white">
            Tax Information
        </h2>

    </div>

    <div class="grid grid-cols-1 gap-5 p-5 md:grid-cols-2 lg:grid-cols-3">

        <div>
            <label class="label">PAN</label>
            <input
                type="text"
                name="pan"
                value="{{ $value('pan') }}"
                class="input"
                placeholder="PAN"
            >
        </div>

        <div>
            <label class="label">GST No.</label>
            <input
                type="text"
                name="gst_no"
                value="{{ $value('gst_no') }}"
                class="input"
                placeholder="GST Number"
            >
        </div>

        <div>
            <label class="label">TIN No.</label>
            <input
                type="text"
                name="tin_no"
                value="{{ $value('tin_no') }}"
                class="input"
                placeholder="TIN Number"
            >
        </div>

        <div>
            <label class="label">IT Ward</label>
            <input
                type="text"
                name="it_ward"
                value="{{ $value('it_ward') }}"
                class="input"
                placeholder="IT Ward"
            >
        </div>

        <div>
            <label class="label">GST Dealer Type</label>
            <input
                type="text"
                name="type_of_dealer_gst"
                value="{{ $value('type_of_dealer_gst') }}"
                class="input"
                placeholder="Registered"
            >
        </div>

        <div>
            <label class="label">Tax Type</label>
            <input
                type="text"
                name="tax_type"
                value="{{ $value('tax_type') }}"
                class="input"
                placeholder="Tax Type"
            >
        </div>

    </div>

</div>


{{-- Banking --}}
<div class="mt-6 rounded-xl border border-slate-200 bg-white shadow-sm">

    <div class="border-b border-slate-200 bg-teal-700 px-5 py-3">

        <h2 class="font-semibold text-white">
            Banking Information
        </h2>

    </div>

    <div class="grid grid-cols-1 gap-5 p-5 md:grid-cols-2 lg:grid-cols-4">

        <div>
            <label class="label">Account No.</label>
            <input
                type="text"
                name="account_no"
                value="{{ $value('account_no') }}"
                class="input"
                placeholder="Account Number"
            >
        </div>

        <div>
            <label class="label">Bank Name</label>
            <input
                type="text"
                name="bank_name"
                value="{{ $value('bank_name') }}"
                class="input"
                placeholder="Bank Name"
            >
        </div>

        <div>
            <label class="label">IFSC Code</label>
            <input
                type="text"
                name="ifsc_code"
                value="{{ $value('ifsc_code') }}"
                class="input"
                placeholder="IFSC"
            >
        </div>

        <div>
            <label class="label">SWIFT Code</label>
            <input
                type="text"
                name="swift_code"
                value="{{ $value('swift_code') }}"
                class="input"
                placeholder="SWIFT"
            >
        </div>

    </div>

</div>


{{-- Accounting --}}
<div class="mt-6 rounded-xl border border-slate-200 bg-white shadow-sm">

    <div class="border-b border-slate-200 bg-teal-700 px-5 py-3">

        <h2 class="font-semibold text-white">
            Accounting Information
        </h2>

    </div>

    <div class="grid grid-cols-1 gap-5 p-5 md:grid-cols-2 lg:grid-cols-4">

        <div>
            <label class="label">Opening Balance</label>

            <input
                type="number"
                step="0.0001"
                name="opening_balance"
                value="{{ $value('opening_balance') }}"
                class="input"
                placeholder="Opening Balance"
            >
        </div>

        <div>
            <label class="label">Closing Balance</label>

            <input
                type="number"
                step="0.0001"
                name="closing_balance"
                value="{{ $value('closing_balance') }}"
                class="input"
                placeholder="Closing Balance"
            >
        </div>

        <div>
            <label class="label">Credit Days - Sale</label>

            <input
                type="number"
                name="credit_days_sale"
                value="{{ $value('credit_days_sale') }}"
                class="input"
                placeholder="0"
            >
        </div>

        <div>
            <label class="label">Credit Days - Purchase</label>

            <input
                type="number"
                name="credit_days_purchase"
                value="{{ $value('credit_days_purchase') }}"
                class="input"
                placeholder="0"
            >
        </div>

    </div>

</div>


{{-- BUSY Advanced --}}
<div class="mt-6 rounded-xl border border-slate-200 bg-white shadow-sm">

    <div class="border-b border-slate-200 bg-slate-800 px-5 py-3">

        <h2 class="font-semibold text-white">
            BUSY Advanced Fields
        </h2>

    </div>

    <div class="grid grid-cols-1 gap-5 p-5 md:grid-cols-2 lg:grid-cols-4">

        @foreach([
            'price_level' => 'Price Level',
            'price_level_purchase' => 'Purchase Price Level',
            'cheque_print_name' => 'Cheque Print Name',
            'reverse_charge_type' => 'Reverse Charge Type',
            'input_type' => 'Input Type',
            'c3' => 'C3',
            'c4' => 'C4',
            'c5' => 'C5',
            'c8' => 'C8',
        ] as $field => $label)

            <div>

                <label class="label">
                    {{ $label }}
                </label>

                <input
                    type="text"
                    name="{{ $field }}"
                    value="{{ $value($field) }}"
                    class="input"
                    placeholder="{{ $label }}"
                >

            </div>

        @endforeach

    </div>

</div>


{{-- BUSY Sync Status --}}
@if($party)

<div class="mt-6 rounded-xl border border-slate-200 bg-white shadow-sm">

    <div class="border-b border-slate-200 bg-slate-800 px-5 py-3">

        <h2 class="font-semibold text-white">
            BUSY Synchronization
        </h2>

    </div>

    <div class="grid grid-cols-1 gap-5 p-5 md:grid-cols-3">

        <div>
            <p class="text-xs font-semibold uppercase text-slate-400">
                BUSY Master ID
            </p>

            <p class="mt-1 font-semibold text-slate-800">
                {{ $party->busyparty_id ?: 'Not Synced' }}
            </p>
        </div>

        <div>
            <p class="text-xs font-semibold uppercase text-slate-400">
                Sync Status
            </p>

            <p class="mt-1 font-semibold
                {{ $party->busy_sync_status === 'Synced'
                    ? 'text-emerald-600'
                    : ($party->busy_sync_status === 'Failed'
                        ? 'text-red-600'
                        : 'text-amber-600') }}"
            >
                {{ $party->busy_sync_status }}
            </p>
        </div>

        <div>
            <p class="text-xs font-semibold uppercase text-slate-400">
                Last Sync
            </p>

            <p class="mt-1 font-semibold text-slate-800">
                {{ $party->busy_synced_at?->format('d M Y H:i:s') ?? 'Never' }}
            </p>
        </div>

    </div>

    @if($party->busy_sync_message)

        <div class="border-t border-slate-200 bg-red-50 p-5">

            <p class="text-sm font-semibold text-red-700">
                Last BUSY Error
            </p>

            <p class="mt-1 text-sm text-red-600">
                {{ $party->busy_sync_message }}
            </p>

        </div>

    @endif

</div>

@endif


{{-- Small Tailwind helpers --}}
<style>
    .label {
        display: block;
        margin-bottom: 0.375rem;
        font-size: 0.875rem;
        line-height: 1.25rem;
        font-weight: 600;
        color: rgb(51 65 85);
    }

    .input {
        width: 100%;
        border-radius: 0.5rem;
        border: 1px solid rgb(203 213 225);
        background-color: white;
        padding: 0.625rem 0.75rem;
        font-size: 0.875rem;
        outline: none;
    }

    .input:focus {
        border-color: rgb(13 148 136);
        box-shadow: 0 0 0 3px rgb(204 251 241);
    }
</style>