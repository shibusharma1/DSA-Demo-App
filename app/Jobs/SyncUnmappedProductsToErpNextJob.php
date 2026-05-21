<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncUnmappedProductsToErpNextJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public function handle(): void
    {
        $hub = app(\App\Services\IntegrationHubService::class);

        $query = Product::query()
            ->whereNull('erpnext_id');

        $total = $query->count();

        if ($total === 0) {

            Log::info('All products are already synced with ERPNext.');

            return;
        }

        Log::info("Starting ERPNext product sync for {$total} products.");

        $query->orderBy('id')
            ->chunkById(50, function ($products) use ($hub) {

                foreach ($products as $product) {

                    try {

                        $hub->fireEvent(
                            eventType: 'item.created',
                            entityType: 'item',
                            entityId: $product->id,
                            payload: [
                                'data' => $product->toArray()
                            ],
                        );

                        Log::info('Product sync dispatched', [
                            'product_id' => $product->id,
                        ]);
                    } catch (\Throwable $e) {

                        Log::error('Product sync failed', [
                            'product_id' => $product->id,
                            'message' => $e->getMessage(),
                        ]);
                    }
                }
            });

        Log::info('ERPNext product sync completed.');
    }
}
