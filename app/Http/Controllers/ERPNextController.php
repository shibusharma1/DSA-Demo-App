<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ERPNextController extends Controller
{
    public function index() {}

    public function store(Request $request)
    {
        Log::info('Webhook received', $request->all());

        return response()->json(['status' => 'ok']);
    }
}
