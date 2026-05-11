<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\IntegrationHubService;

class PaymentObserver
{
    public function __construct(private IntegrationHubService $hub) {}

    public function created(Payment $payment): void
    {
        $this->hub->fireEvent(
            eventType: 'payment.created',
            entityType: 'payment',
            entityId: $payment->id,
            payload: [
                'data' => $payment->toArray(),

                'customer' => [
                    'id'           => $payment->customer->id ?? null,
                    'zoho_id'      => $payment->customer->zoho_id ?? null,
                    'erpnext_id'   => $payment->customer->erpnext_id ?? null,
                    'tally_id'     => $payment->customer->tally_id ?? null,
                    'quickbooks_id' => $payment->customer->quickbooks_id ?? null,
                    'busy_id'      => $payment->customer->busy_id ?? null,
                    'sap_id'       => $payment->customer->sap_id ?? null,
                ],
            ],
        );
    }

    public function updated(Payment $payment): void
    {
        $this->hub->fireEvent(
            eventType: 'payment.updated',
            entityType: 'payment',
            entityId: $payment->id,
            payload: [
                'data' => $payment->toArray(),

                'customer' => $payment->customer?->only([
                    'id',
                    'zoho_id',
                    'erpnext_id',
                    'tally_id',
                    'quickbooks_id',
                    'busy_id',
                    'sap_id',
                ]),
            ],
        );
    }

    public function deleted(Payment $payment): void
    {
        $this->hub->fireEvent(
            eventType: 'payment.deleted',
            entityType: 'payment',
            entityId: $payment->id,
            payload: ['id' => $payment->id],
        );
    }
}
