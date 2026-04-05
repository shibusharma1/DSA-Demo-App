<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\IntegrationHubService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(
        private IntegrationHubService $hub
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $customers = Customer::latest()->paginate(10);
        return view('indexCustomer', compact('customers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('createCustomer');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'contact_name' => 'required|string|max:255',
            'email'        => 'required|email|unique:customers,email',
            'company_name' => 'nullable|string|max:255',
            'phone'        => 'nullable|string|max:50',
            'zb_id'        => 'nullable|string|max:255',
        ]);

        $customer = Customer::create($data);

        // Fire event to Integration Hub
        $this->hub->fireEvent(
            eventType: 'customer.created',
            entityType: 'customer',
            entityId: $customer->id,
            payload: $customer->toArray()
        );

        return redirect()->route('customers.index')
            ->with('success', 'Customer created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        return view('editCustomer', compact('customer'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'contact_name' => 'sometimes|required|string|max:255',
            'email'        => 'sometimes|required|email|unique:customers,email,' . $customer->id,
            'company_name' => 'sometimes|nullable|string|max:255',
            'phone'        => 'sometimes|nullable|string|max:50',
            'zb_id'        => 'sometimes|nullable|string|max:255',
        ]);

        $customer->update($data);

        // Fire event to Integration Hub
        $this->hub->fireEvent(
            eventType: 'customer.updated',
            entityType: 'customer',
            entityId: $customer->id,
            payload: $customer->toArray()
        );

        return redirect()->route('customers.index')
            ->with('success', 'Customer updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        // Fire deletion event first
        $this->hub->fireEvent(
            eventType: 'customer.deleted',
            entityType: 'customer',
            entityId: $customer->id,
            payload: $customer->toArray()
        );

        $customer->delete();

        return redirect()->back()
            ->with('success', 'Customer deleted successfully.');
    }

    /**
     * Optional: Update zb_id specifically (from webhook or API call)
     */
    public function updateZbId(Request $request, Customer $customer)
    {
        $customer->update([
            'zb_id' => $request->zb_id
        ]);

        return response()->json([
            'message' => 'zb_id updated successfully'
        ]);
    }
}
