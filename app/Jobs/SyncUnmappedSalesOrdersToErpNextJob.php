<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncUnmappedSalesOrdersToErpNextJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public function handle(): void
    {
        $hub = app(\App\Services\IntegrationHubService::class);

        $query = Order::query()
            ->whereNull('erpnext_id');

        $total = $query->count();

        if ($total === 0) {

            Log::info('All sales orders are already synced with ERPNext.');

            return;
        }

        Log::info("Starting ERPNext sales order sync for {$total} orders.");

        $query->orderBy('id')
            ->chunkById(50, function ($orders) use ($hub) {

                foreach ($orders as $order) {

                    try {

                        $hub->fireEvent(
                            eventType: 'sales_order.created',
                            entityType: 'sales_order',
                            entityId: $order->id,
                            payload: [
                                'data' => $order->load('items')->toArray(),

                                'customer' => [
                                    'id' => $order->customer->id ?? null,
                                    'erpnext_id' => $order->customer->erpnext_id ?? null,
                                ],
                            ],
                        );

                        Log::info('Sales order sync dispatched', [
                            'order_id' => $order->id,
                        ]);

                    } catch (\Throwable $e) {

                        Log::error('Sales order sync failed', [
                            'order_id' => $order->id,
                            'message' => $e->getMessage(),
                        ]);
                    }
                }
            });

        Log::info('ERPNext sales order sync completed.');
    }
}