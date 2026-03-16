<?php

use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookSenderController;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/send-webhook', [WebhookSenderController::class, 'sendWebhook']);

Route::get('/customer',[CustomerController::class,'create']);
Route::post('/customer',[CustomerController::class,'store']);