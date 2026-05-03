<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::latest()->paginate(10);
        return view('clients.index', compact('clients'));
    }

    public function create()
    {
        return view('clients.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'company_name'  => 'nullable|string|max:255',
            'email'         => 'nullable|email',
            'phone'         => 'nullable|string',
            'mobile'        => 'nullable|string',
            'address_1'     => 'nullable|string',
            'address_2'     => 'nullable|string',
            'location'      => 'nullable|string',
            'pan'           => 'nullable|string',
            'website'       => 'nullable|string',
            'credit_limit'  => 'nullable|numeric',
            'credit_days'   => 'nullable|integer',
            'status'        => 'required|string',
        ]);

        Client::create($data);

        return redirect()->route('clients.index')
            ->with('success', 'Client created successfully.');
    }

    public function edit(Client $client)
    {
        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $data = $request->validate([
            'name'          => 'sometimes|required|string|max:255',
            'company_name'  => 'nullable|string|max:255',
            'email'         => 'nullable|email',
            'phone'         => 'nullable|string',
            'mobile'        => 'nullable|string',
            'address_1'     => 'nullable|string',
            'address_2'     => 'nullable|string',
            'location'      => 'nullable|string',
            'pan'           => 'nullable|string',
            'website'       => 'nullable|string',
            'credit_limit'  => 'nullable|numeric',
            'credit_days'   => 'nullable|integer',
            'status'        => 'required|string',
        ]);

        $client->update($data);

        return redirect()->route('clients.index')
            ->with('success', 'Client updated successfully.');
    }

    public function destroy(Client $client)
    {
        $client->delete();

        return back()->with('success', 'Client deleted successfully.');
    }
}