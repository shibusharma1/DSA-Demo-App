<?php

use App\Http\Controllers\ApiCredentialController;
use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookSenderController;

Route::get('/', function () {
    return view('welcome');
});


// Customer CRUD
Route::get('/customers', [CustomerController::class,'index'])->name('customers.index');
Route::get('/customer/create', [CustomerController::class,'create'])->name('customers.create');
Route::post('/customer', [CustomerController::class,'store'])->name('customers.store');
Route::get('/customer/{customer}/edit', [CustomerController::class,'edit'])->name('customers.edit');
Route::put('/customer/{customer}', [CustomerController::class,'update'])->name('customers.update');
Route::delete('/customer/{customer}', [CustomerController::class,'destroy'])->name('customers.destroy');



Route::get('api-credentials/{service_type?}/{company_id?}',[ApiCredentialController::class,'form'])->name('api-credentials.form'); 
Route::post('api-credentials/save', [ApiCredentialController::class, 'save'])->name('api-credentials.save');