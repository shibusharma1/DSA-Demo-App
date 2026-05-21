<?php

namespace App\Http\Controllers;

use App\Jobs\SyncUnmappedCustomersToErpNextJob;
use App\Jobs\SyncUnmappedPaymentsToErpNextJob;
use App\Jobs\SyncUnmappedProductsToErpNextJob;
use App\Jobs\SyncUnmappedSalesOrdersToErpNextJob;

class SyncController extends Controller
{
    public function syncCustomers()
    {
        SyncUnmappedCustomersToErpNextJob::dispatch();

        return response()->json([
            'success' => true,
            'message' => 'Sync job has been started successfully!'
        ]);
    }
    public function syncProducts()
    {
        SyncUnmappedProductsToErpNextJob::dispatch();

        return response()->json([
            'success' => true,
            'message' => 'Product sync started successfully!'
        ]);
    }

    public function syncPayments()
    {
        SyncUnmappedPaymentsToErpNextJob::dispatch();

        return response()->json([
            'success' => true,
            'message' => 'Payment sync started successfully!'
        ]);
    }

    public function syncSalesOrders()
    {
        SyncUnmappedSalesOrdersToErpNextJob::dispatch();

        return response()->json([
            'success' => true,
            'message' => 'Sales order sync started successfully!'
        ]);
    }
}
