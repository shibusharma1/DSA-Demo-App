<?php

// use App\Http\Controllers\CustomerController;
use App\Http\Controllers\Api\V1\IntegrationTokenController;
use App\Http\Controllers\Company\Admin\ERPNextOAuthController;
use App\Http\Controllers\Integration\InboundIntegrationController;
use App\Http\Controllers\Integration\SyncIdController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')
    ->middleware(['internal.key'])
    ->group(function () {
        Route::get('/integrations/token', [IntegrationTokenController::class, 'show']);
    });

Route::post('/integration/inbound',[InboundIntegrationController::class,'handle']);

Route::post('/integration/sync-ids', [SyncIdController::class, 'handle'])
    ->middleware('verify.ih.signature');


Route::post('erpnext/customer', [ERPNextOAuthController::class, 'store'])->name('erpnext.store');
