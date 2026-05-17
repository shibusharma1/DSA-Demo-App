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
            eventType: 'sales_order.created',
            entityType: 'sales_order',
            entityId: $order->id,
            payload: [
                'data' => $order->load('items')->toArray(),

                'customer' => [
                    'id'           => $order->customer->id ?? null,
                    'zoho_id'      => $order->customer->zoho_id ?? null,
                    'erpnext_id'   => $order->customer->erpnext_id ?? null,
                    'tally_id'     => $order->customer->tally_id ?? null,
                    'quickbooks_id' => $order->customer->quickbooks_id ?? null,
                    'busy_id'      => $order->customer->busy_id ?? null,
                    'sap_id'       => $order->customer->sap_id ?? null,
                ],
                
            ],
        );
    }

    public function updated(Order $order): void
    {
        $this->hub->fireEvent(
            eventType: 'sales_order.updated',
            entityType: 'sales_order',
            entityId: $order->id,
            payload: [
                'data' => $order->load('items')->toArray(),

                'customer' => [
                    'id'           => $order->customer->id ?? null,
                    'zoho_id'      => $order->customer->zoho_id ?? null,
                    'erpnext_id'   => $order->customer->erpnext_id ?? null,
                    'tally_id'     => $order->customer->tally_id ?? null,
                    'quickbooks_id' => $order->customer->quickbooks_id ?? null,
                    'busy_id'      => $order->customer->busy_id ?? null,
                    'sap_id'       => $order->customer->sap_id ?? null,
                ],
            ],
        );
    }

    public function deleted(Order $order): void
    {
        $this->hub->fireEvent(
            eventType: 'sales_order.deleted',
            entityType: 'sales_order',
            entityId: $order->id,
            payload: ['id' => $order->id],
        );
    }
}
