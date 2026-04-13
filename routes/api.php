<?php

// use App\Http\Controllers\CustomerController;
use App\Http\Controllers\Api\V1\IntegrationTokenController;
// use Symfony\Component\Routing\Route;
use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\Integration\InboundController;




// Route::patch('/customers/{customer}/zb-id', [CustomerController::class, 'updateZbId']);


Route::prefix('v1')
    ->middleware(['internal.key'])
    ->group(function () {
        Route::get('/integrations/token', [IntegrationTokenController::class, 'show']);
    });

// Route::post('/integration/inbound', InboundController::class);
use App\Http\Controllers\Integration\SyncIdController;

Route::post('/integration/sync-ids', [SyncIdController::class, 'handle'])
    ->middleware('verify.ih.signature');
