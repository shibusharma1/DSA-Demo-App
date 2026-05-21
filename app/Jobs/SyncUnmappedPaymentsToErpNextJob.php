<?php

namespace App\Jobs;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncUnmappedPaymentsToErpNextJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public function handle(): void
    {
        $hub = app(\App\Services\IntegrationHubService::class);

        $query = Payment::query()
            ->whereNull('erpnext_id');

        $total = $query->count();

        if ($total === 0) {

            Log::info('All payments are already synced with ERPNext.');

            return;
        }

        Log::info("Starting ERPNext payment sync for {$total} payments.");

        $query->orderBy('id')
            ->chunkById(50, function ($payments) use ($hub) {

                foreach ($payments as $payment) {

                    try {

                        $hub->fireEvent(
                            eventType: 'payment.created',
                            entityType: 'payment',
                            entityId: $payment->id,
                            payload: [
                                'data' => $payment->toArray(),

                                'customer' => [
                                    'id' => $payment->customer->id ?? null,
                                    'erpnext_id' => $payment->customer->erpnext_id ?? null,
                                ],
                            ],
                        );

                        Log::info('Payment sync dispatched', [
                            'payment_id' => $payment->id,
                        ]);

                    } catch (\Throwable $e) {

                        Log::error('Payment sync failed', [
                            'payment_id' => $payment->id,
                            'message' => $e->getMessage(),
                        ]);
                    }
                }
            });

        Log::info('ERPNext payment sync completed.');
    }
}