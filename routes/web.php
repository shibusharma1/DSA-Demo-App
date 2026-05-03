<?php

use App\Http\Controllers\Company\Admin\ERPNextOAuthController;
use App\Http\Controllers\Company\Admin\ZohoOAuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProductController;


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

Route::prefix('zoho')->name('zoho.')->group(function () {
    Route::get('/connect',    [ZohoOAuthController::class, 'redirect'])->name('connect');
    Route::get('/callback',   [ZohoOAuthController::class, 'callback'])->name('callback');
    Route::post('/disconnect', [ZohoOAuthController::class, 'disconnect'])->name('disconnect');
});

// Customer CRUD

Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
Route::get('/customer/create', [CustomerController::class, 'create'])->name('customers.create');
Route::post('/customer', [CustomerController::class, 'store'])->name('customers.store');
Route::get('/customer/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
Route::put('/customer/{customer}', [CustomerController::class, 'update'])->name('customers.update');
Route::delete('/customer/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');


Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/product/create', [ProductController::class, 'create'])->name('products.create');
Route::post('/product', [ProductController::class, 'store'])->name('products.store');
Route::get('/product/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
Route::put('/product/{product}', [ProductController::class, 'update'])->name('products.update');
Route::delete('/product/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
Route::get('/client/create', [ClientController::class, 'create'])->name('clients.create');
Route::post('/client', [ClientController::class, 'store'])->name('clients.store');
Route::get('/client/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');
Route::put('/client/{client}', [ClientController::class, 'update'])->name('clients.update');
Route::delete('/client/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');
