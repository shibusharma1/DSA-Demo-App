<?php

namespace App\Http\Controllers;

// use App\Country;
use App\Models\PartyBusy;
use App\Services\busy\DsaToBusyParty;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class BusyPartyController extends Controller
{
    public function __construct(
        private readonly DsaToBusyParty $busyPartyService
    ) {}

    /**
     * List BUSY parties.
     */
    public function index(): View
    {
        // $companyId = company_id();
        $companyId = 1;

        $parties = PartyBusy::query()
            ->where('company_id', $companyId)
            ->latest('id')
            ->paginate(25);

        return view(
            'busy.parties.index',
            compact('parties')
        );
    }

    /**
     * Show create form.
     */
    public function create(): View
    {
        // $countries = Country::query()
        //     ->orderBy('name')
        //     ->get();
        $countries = [
            (object) ['id' => 1, 'name' => 'Nepal'],
            (object) ['id' => 2, 'name' => 'India'],
        ];

        return view(
            'busy.parties.create',
            compact('countries')
        );
    }

    /**
     * Store local party and automatically send it to BUSY.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        $data['company_id'] = 1;
        $data['busy_sync_status'] = 'Pending';

        /*
         * Save locally first.
         *
         * This gives us a local record even when BUSY is
         * temporarily unavailable.
         */
        $party = PartyBusy::create($data);

        $result = $this->busyPartyService->createParty(
            $party->fresh()
        );

        if ($result['success']) {
            return redirect()
                ->route('busy.parties.index')
                ->with(
                    'success',
                    $result['message']
                        . ' BUSY Master ID: '
                        . $result['busyparty_id']
                );
        }

        return redirect()
            ->route('busy.parties.edit', $party)
            ->withInput()
            ->with(
                'error',
                'Customer was created locally, but could not be saved to BUSY. '
                    . $result['error']
            );
    }

    /**
     * Show edit form.
     */
    public function edit(PartyBusy $party): View
    {
        $this->ensureCompanyParty($party);

        $countries = [
            (object) ['id' => 1, 'name' => 'Nepal'],
            (object) ['id' => 2, 'name' => 'India'],
        ];

        return view(
            'busy.parties.edit',
            compact(
                'party',
                'countries'
            )
        );
    }

    /**
     * Update local party and automatically modify BUSY.
     */
    public function update(
        Request $request,
        PartyBusy $party
    ): RedirectResponse {
        $this->ensureCompanyParty($party);

        $data = $this->validatedData($request);

        $party->update($data);

        $result = $this->busyPartyService->updateParty(
            $party->fresh()
        );

        if ($result['success']) {
            return redirect()
                ->route('busy.parties.index')
                ->with(
                    'success',
                    $result['message']
                        . ' BUSY Master ID: '
                        . $result['busyparty_id']
                );
        }

        return redirect()
            ->route('busy.parties.edit', $party)
            ->withInput()
            ->with(
                'error',
                'Customer was updated locally, but BUSY could not be updated. '
                    . $result['error']
            );
    }

    /**
     * Validation + normalized input.
     */
    private function validatedData(
        Request $request
    ): array {
        $data = $request->validate([
            /*
             * Basic
             */
            'company_name' => [
                'required',
                'string',
                'max:255',
            ],

            'name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'alias' => [
                'nullable',
                'string',
                'max:255',
            ],

            'print_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'parent_group' => [
                'required',
                'string',
                'max:255',
            ],

            'bill_by_bill_balancing' => [
                'nullable',
                'boolean',
            ],

            /*
             * Contact
             */
            'phone' => [
                'nullable',
                'string',
                'max:100',
            ],

            'mobile' => [
                'nullable',
                'string',
                'max:100',
            ],

            'whatsapp_no' => [
                'nullable',
                'string',
                'max:100',
            ],

            'fax' => [
                'nullable',
                'string',
                'max:100',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'contact' => [
                'nullable',
                'string',
                'max:255',
            ],

            'cont_dept_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            /*
             * Address
             */
            'address_1' => [
                'nullable',
                'string',
            ],

            'address_2' => [
                'nullable',
                'string',
            ],

            'address_3' => [
                'nullable',
                'string',
            ],

            'address_4' => [
                'nullable',
                'string',
            ],

            // 'country' => [
            //     'nullable',
            //     'integer',
            //     'exists:countries,id',
            // ],

            'phonecode' => [
                'nullable',
                'string',
                'max:20',
            ],

            'state' => [
                'nullable',
                'string',
                'max:255',
            ],

            'city' => [
                'nullable',
                'string',
                'max:255',
            ],

            'area' => [
                'nullable',
                'string',
                'max:255',
            ],

            /*
             * Tax
             */
            'pan' => [
                'nullable',
                'string',
                'max:100',
            ],

            'gst_no' => [
                'nullable',
                'string',
                'max:100',
            ],

            'tin_no' => [
                'nullable',
                'string',
                'max:100',
            ],

            'it_ward' => [
                'nullable',
                'string',
                'max:100',
            ],

            'st37' => [
                'nullable',
                'string',
                'max:100',
            ],

            /*
             * Bank
             */
            'account_no' => [
                'nullable',
                'string',
                'max:100',
            ],

            'bank_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'ifsc_code' => [
                'nullable',
                'string',
                'max:100',
            ],

            'swift_code' => [
                'nullable',
                'string',
                'max:100',
            ],

            /*
             * BUSY custom values
             */
            'c3' => [
                'nullable',
                'string',
                'max:255',
            ],

            'c4' => [
                'nullable',
                'string',
                'max:255',
            ],

            'c5' => [
                'nullable',
                'string',
                'max:255',
            ],

            'c8' => [
                'nullable',
                'string',
                'max:255',
            ],

            /*
             * Configuration
             */
            'supplier_type' => [
                'nullable',
                'string',
                'max:100',
            ],

            'credit_days_sale' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'credit_days_purchase' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'price_level' => [
                'nullable',
                'string',
                'max:100',
            ],

            'price_level_purchase' => [
                'nullable',
                'string',
                'max:100',
            ],

            'tax_type' => [
                'nullable',
                'string',
                'max:255',
            ],

            'type_of_dealer_gst' => [
                'nullable',
                'string',
                'max:100',
            ],

            'cheque_print_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'reverse_charge_type' => [
                'nullable',
                'string',
                'max:255',
            ],

            'input_type' => [
                'nullable',
                'string',
                'max:255',
            ],

            /*
             * Accounting
             */
            'opening_balance' => [
                'nullable',
                'numeric',
            ],

            'closing_balance' => [
                'nullable',
                'numeric',
            ],

            /*
             * Local status
             */
            'status' => [
                'required',
                'in:Active,Inactive',
            ],
        ]);

        $data['bill_by_bill_balancing'] =
            $request->boolean('bill_by_bill_balancing');

        /*
         * Empty strings become NULL for cleaner database data.
         */
        foreach ($data as $key => $value) {
            if (
                is_string($value)
                && trim($value) === ''
            ) {
                $data[$key] = null;
            }
        }

        return $data;
    }

    /**
     * Prevent accessing another company's party.
     */
    private function ensureCompanyParty(
        PartyBusy $party
    ): void {
        abort_unless(
            (int) $party->company_id === 1,
            404
        );
    }
}
