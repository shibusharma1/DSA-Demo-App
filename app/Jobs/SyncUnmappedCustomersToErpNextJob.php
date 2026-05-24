<?php

namespace App\Jobs;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncUnmappedCustomersToErpNextJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public function handle(): void
    {
        $hub = app(\App\Services\IntegrationHubService::class);

        $query = Customer::query()
            ->whereNull('erpnext_id');

        $total = $query->count();

        if ($total === 0) {

            Log::info('All customers are already synced with ERPNext.');

            return;
        }

        Log::info("Starting ERPNext customer sync for {$total} customers.");

        $success = 0;
        $failed = 0;

        $query->orderBy('id')
            ->chunkById(50, function ($customers) use ($hub, &$success, &$failed) {

                foreach ($customers as $customer) {

                    try {

                        $hub->fireEvent(
                            eventType: 'customer.created',
                            entityType: 'customer',
                            entityId: $customer->id,
                            payload: [
                                'client' => $customer->toArray()
                            ],
                        );

                        // refresh customer after sync
                        $customer->refresh();

                        if ($customer->erpnext_id) {

                            $success++;

                            Log::info('Customer synced successfully', [
                                'customer_id' => $customer->id,
                                'erpnext_id' => $customer->erpnext_id,
                                'Success' => $success,
                            ]);
                        } else {

                            $failed++;

                            Log::warning('Customer sync failed (erpnext_id still null)', [
                                'customer_id' => $customer->id,
                                'Failed' => $failed,
                            ]);
                        }
                    } catch (\Throwable $e) {

                        $failed++;

                        Log::error('Customer sync exception', [
                            'customer_id' => $customer->id,
                            'message' => $e->getMessage(),
                            'Failed' => $failed,
                        ]);
                    }
                }
            });

        Log::info('ERPNext sync completed', [
            'success' => $success,
            'failed' => $failed,
        ]);
    }
}
