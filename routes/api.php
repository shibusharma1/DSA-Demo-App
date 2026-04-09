<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\Api\V1\IntegrationTokenController;
// use Symfony\Component\Routing\Route;
use Illuminate\Support\Facades\Route;



Route::patch('/customers/{customer}/zb-id', [CustomerController::class, 'updateZbId']);


Route::prefix('v1')
    ->middleware(['internal.key'])
    ->group(function () {
        Route::get('/integrations/token', [IntegrationTokenController::class, 'show']);
    });
