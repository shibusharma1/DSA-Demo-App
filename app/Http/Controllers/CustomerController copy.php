<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Spatie\WebhookServer\WebhookCall;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('createCustomer');
    }

    
    public function store(Request $request)
    {
        // Validate the incoming request
        $data = $request->validate([
            'contact_name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email',
            'company_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'zb_id' => 'nullable|string|max:255', // Zoho Books ID, optional
        ]);

        // Save data in the database
        $customer = Customer::create([
            'contact_name' => $data['contact_name'],
            'email' => $data['email'],
            'company_name' => $data['company_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'zb_id' => $data['zb_id'] ?? null,
        ]);

        // Prepare webhook payload
        $webhookData = [
            'event' => 'user.created',
            'user' => [
                'id' => $customer->id, // Use the database ID
                'contact_name' => $customer->contact_name,
                'email' => $customer->email,
                'company_name' => $customer->company_name,
                'phone' => $customer->phone,
                'zb_id' => $customer->zb_id,
            ]
        ];

        // Send webhook
        WebhookCall::create()
            ->url('http://127.0.0.1:8001/api/webhook')
            ->payload($webhookData)
            ->useSecret('super-secret-key')
            ->dispatch();

        return redirect()->back()->with('success', 'Customer created and webhook sent successfully');
    }


    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        // Validate only fields that are sent in the request
        $data = $request->validate([
            'contact_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:customers,email,' . $customer->id,
            'company_name' => 'sometimes|nullable|string|max:255',
            'phone' => 'sometimes|nullable|string|max:50',
            'zb_id' => 'sometimes|nullable|string|max:255',
        ]);

        // Update only provided fields
        $customer->fill($data)->save();

        // Optional: prepare webhook payload to notify other app
        $webhookData = [
            'event' => 'user.updated',
            'user' => [
                'id' => $customer->id,
                'contact_name' => $customer->contact_name,
                'email' => $customer->email,
                'company_name' => $customer->company_name,
                'phone' => $customer->phone,
                'zb_id' => $customer->zb_id,
            ]
        ];

        WebhookCall::create()
            ->url('http://127.0.0.1:8001/api/webhook')
            ->payload($webhookData)
            ->useSecret('super-secret-key')
            ->dispatch();

        return redirect()->back()->with('success', 'Customer updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        //
    }
    
    public function updateZbId(Request $request, Customer $customer)
    {
        \Log::info('Update ZB ID request', [
            'customer_id' => $customer->id,
            'payload' => $request->all()
        ]);

        $customer->update([
            'zb_id' => $request->zb_id
        ]);

        return response()->json([
            'message' => 'zb_id updated successfully'
        ]);
    }
}