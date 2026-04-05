<?php

namespace App\Http\Controllers;

use App\Models\ApiCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ApiCredentialController extends Controller
{
    // Show form for create/edit
    public function form($service_type = null, $company_id = null)
    {

        $credential = null;

        if ($service_type && $company_id) {

            $response = Http::get(
                "http://127.0.0.1:8001/api/api-credentials/{$service_type}/{$company_id}"
            );

            if ($response->successful()) {
                $credential = (object) $response->json();
            }
        }

        return view('api_credentials.form', compact('credential', 'service_type'));
    }

    // Save or update
    // public function save(Request $request)
    // {

    //     $request->validate([
    //         'client_id' => 'required|string|max:255',
    //         'client_secret' => 'required|string|max:255',
    //         'redirect_uri' => 'required|url',
    //         'accounts_url' => 'required|url',
    //         'api_base' => 'required|url',
    //         'service_type' => 'required|string|max:50',
    //     ]);

    //     // dd($request->all());

    //     $data = $request->only([
    //         'client_id',
    //         'client_secret',
    //         'redirect_uri',
    //         'accounts_url',
    //         'api_base',
    //         'service_type',
    //         'access_token',
    //         'expired_at',
    //         'refresh_token',
    //         'revoked_at',
    //         'oauth_type',
    //         'scope',
    //     ]);

    //     // Add defaults
    //     $data['company_id'] = 1;
    //     $data['organization_id'] = 1;
    //     $data['created_by'] = 1;

    //     // Check if record exists
    //     $credential = ApiCredential::where('service_type', $data['service_type'])
    //         ->where('company_id', 1)
    //         ->first();

    //     if ($credential) {
    //         $credential->update($data);
    //         $message = 'API Credential updated successfully!';
    //     } else {
    //         ApiCredential::create($data);
    //         $message = 'API Credential created successfully!';
    //     }

    //     return redirect()->back()->with('success', $message);
    // }

    public function save(Request $request)
    {
        $request->validate([
            'client_id' => 'required|string|max:255',
            'client_secret' => 'required|string|max:255',
            'redirect_uri' => 'required|url',
            'accounts_url' => 'required|url',
            'api_base' => 'required|url',
            'service_type' => 'required|string|max:50',
        ]);

        $data = $request->only([
            'client_id',
            'client_secret',
            'redirect_uri',
            'accounts_url',
            'api_base',
            'service_type',
            'access_token',
            'expired_at',
            'refresh_token',
            'revoked_at',
            'oauth_type',
            'scope',
        ]);

        $data['company_id'] = 1;
        $data['organization_id'] = 1;
        $data['created_by'] = 1;

        // Send to API
        $response = Http::post(
            "http://127.0.0.1:8001/api/api-credentials/save",
            $data
        );

        if ($response->successful()) {
            return redirect()->back()->with(
                'success',
                $response->json()['message'] ?? 'Saved successfully'
            );
        }

        return redirect()->back()->with(
            'error',
            'API request failed'
        );
    }
}
