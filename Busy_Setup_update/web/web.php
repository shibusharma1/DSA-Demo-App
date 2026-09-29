<?php

use App\Http\Controllers\BusySyncController;
use App\Http\Controllers\Company\Admin\ERPNextOAuthController;
use App\Http\Controllers\Company\Admin\ZohoOAuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CustomerSyncController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\BusyPartyController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SyncController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/busy/sync/parties', [BusySyncController::class, 'parties']);
Route::get('/busy/sync/products', [BusySyncController::class, 'products']);
Route::get('/busy/sync/collections', [BusySyncController::class, 'collections']);
Route::get('/busy/sync/units', [BusySyncController::class, 'units']);
Route::get('/busy/sync/taxes', [BusySyncController::class, 'taxes']);
Route::get('/busy/sync/item-categories', [BusySyncController::class, 'itemCategories']);
Route::get('/busy/sync/outstandings', [BusySyncController::class, 'outstandings']);
Route::post('/orders/{order}/sync-busy', [OrderController::class, 'syncToBusy'])->name('orders.sync-busy');



/* Orders */
Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
Route::get('/orders/{order}/edit', [OrderController::class, 'edit'])->name('orders.edit');
Route::put('/orders/{order}', [OrderController::class, 'update'])->name('orders.update');
Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
Route::get('/orders/product/{product}', [OrderController::class, 'product'])->name('orders.product');

/*BUSY Parties */
Route::prefix('busy/parties')->name('busy.parties.')->group(function () {
    Route::get('/', [BusyPartyController::class, 'index'])->name('index');
    Route::get('/create', [BusyPartyController::class, 'create'])->name('create');
    Route::post('/', [BusyPartyController::class, 'store'])->name('store');
    Route::get('/{party}/edit', [BusyPartyController::class, 'edit'])->name('edit');
    Route::put('/{party}', [BusyPartyController::class, 'update'])->name('update');
});

Route::prefix('collections')->name('collections.')->group(function () {
        Route::get('/',[CollectionController::class, 'index'])->name('index');
        Route::get('/create',[CollectionController::class, 'create'])->name('create');
        Route::post('/',[CollectionController::class, 'store'])->name('store');
        Route::get('/{collection}',[CollectionController::class, 'show'])->name('show');
        Route::get('/{collection}/edit',[CollectionController::class, 'edit'])->name('edit');
        Route::put('/{collection}',[CollectionController::class, 'update'])->name('update');
        Route::delete('/{collection}',[CollectionController::class, 'destroy'])->name('destroy');
        Route::post('/{collection}/sync-busy',[CollectionController::class, 'syncBusy'])->name('sync-busy');
    });
