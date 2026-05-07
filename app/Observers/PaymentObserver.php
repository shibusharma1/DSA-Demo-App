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
            payload: $payment->toArray(),
        );
    }

    public function updated(Payment $payment): void
    {
        $this->hub->fireEvent(
            eventType: 'payment.updated',
            entityType: 'payment',
            entityId: $payment->id,
            payload: $payment->toArray(),
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
