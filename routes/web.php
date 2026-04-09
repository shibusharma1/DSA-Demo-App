<?php

use App\Http\Controllers\ApiCredentialController;
use App\Http\Controllers\Company\Admin\ERPNextOAuthController;
use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\WebhookSenderController;

Route::get('/', function () {
    return view('welcome');
});



Route::prefix('erpnext')->name('erpnext.')->group(function () {
    Route::get('/connect',    [ERPNextOAuthController::class, 'showConnect'])->name('connect');
    Route::get('/callback',   [ERPNextOAuthController::class, 'callback'])->name('callback');
    Route::post('/disconnect', [ERPNextOAuthController::class, 'disconnect'])->name('disconnect');
    Route::get('/ping',       [ERPNextOAuthController::class, 'ping'])->name('ping');
});

// Customer CRUD
Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
Route::get('/customer/create', [CustomerController::class, 'create'])->name('customers.create');
Route::post('/customer', [CustomerController::class, 'store'])->name('customers.store');
Route::get('/customer/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
Route::put('/customer/{customer}', [CustomerController::class, 'update'])->name('customers.update');
Route::delete('/customer/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');



Route::get('api-credentials/{service_type?}/{company_id?}', [ApiCredentialController::class, 'form'])->name('api-credentials.form');
Route::post('api-credentials/save', [ApiCredentialController::class, 'save'])->name('api-credentials.save');
