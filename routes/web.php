<?php

use App\Http\Controllers\Company\Admin\ERPNextOAuthController;
use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;

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
