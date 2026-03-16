<?php

use App\Http\Controllers\CustomerController;
// use Symfony\Component\Routing\Route;
use Illuminate\Support\Facades\Route;

Route::patch('/customers/{customer}/zb-id', [CustomerController::class, 'updateZbId']);
