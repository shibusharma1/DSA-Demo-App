<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\WebhookServer\WebhookCall;

class WebhookSenderController extends Controller
{

    /**
     * Send webhook to another application
     */
    public function sendWebhook()
    {

        // Step 1: Define payload data
        $data = [
            'event' => 'user.created',
            'user' => [
                'id' => 10,
                'name' => 'Shibu Sharma',
                'email' => 'shibu@example.com'
            ]
        ];

        /*
        |--------------------------------------------------------------------------
        | WebhookCall::create()
        |--------------------------------------------------------------------------
        | Create a new webhook request instance
        */
        WebhookCall::create()

            /*
            |--------------------------------------------------------------------------
            | url()
            |--------------------------------------------------------------------------
            | The endpoint of the receiving application (App B)
            */
            ->url('http://127.0.0.1:8001/api/webhook')

            /*
            |--------------------------------------------------------------------------
            | payload()
            |--------------------------------------------------------------------------
            | Data that will be sent as JSON body
            */
            ->payload($data)

            /*
            |--------------------------------------------------------------------------
            | useSecret()
            |--------------------------------------------------------------------------
            | Shared secret used for signature verification
            */
            ->useSecret('super-secret-key')

            /*
            |--------------------------------------------------------------------------
            | dispatch()
            |--------------------------------------------------------------------------
            | Send webhook asynchronously via queue
            */
            ->dispatch();


        return response()->json([
            'message' => 'Webhook sent successfully'
        ]);
    }
}