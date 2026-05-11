<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\IntegrationHubService;

class OrderObserver
{
    public function __construct(private IntegrationHubService $hub) {}

    public function created(Order $order): void
    {
        $this->hub->fireEvent(
            eventType: 'order.created',
            entityType: 'order',
            entityId: $order->id,
            payload: [
                'data' => $order->toArray()
            ],
        );
    }

    public function updated(Order $order): void
    {
        $this->hub->fireEvent(
            eventType: 'order.updated',
            entityType: 'order',
            entityId: $order->id,
            payload: [
                'data' => $order->toArray()
            ],
        );
    }

    public function deleted(Order $order): void
    {
        $this->hub->fireEvent(
            eventType: 'order.deleted',
            entityType: 'order',
            entityId: $order->id,
            payload: ['id' => $order->id],
        );
    }
}
