<?php
// In your DSA application:
// app/Observers/InvoiceObserver.php

namespace App\Observers;

use App\Models\Customer;
use App\Services\IntegrationHubService;

class CustomerObserver
{
    public function __construct(private IntegrationHubService $hub) {}

    public function created(Customer $customer): void
    {
        $this->hub->fireEvent(
            eventType:  'customer.created',
            entityType: 'customer',
            entityId:   $customer->id,
            payload:    $customer->toArray(),
        );
    }

    public function updated(Customer $customer): void
    {
        $this->hub->fireEvent(
            eventType:  'customer.updated',
            entityType: 'customer',
            entityId:   $customer->id,
            payload:    $customer->toArray(),
        );
    }

    public function deleted(Customer $customer): void
    {
        $this->hub->fireEvent(
            eventType:  'customer.deleted',
            entityType: 'customer',
            entityId:   $customer->id,
            payload:    ['id' => $customer->id],
        );
    }
}