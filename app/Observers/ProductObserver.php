<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\IntegrationHubService;

class ProductObserver
{
    public function __construct(private IntegrationHubService $hub) {}

    public function created(Product $product): void
    {
        $this->hub->fireEvent(
            eventType:  'item.created',
            entityType: 'item',
            entityId:   $product->id,
            payload:    [
                'data' => $product->toArray()
            ],
        );
    }

    public function updated(Product $product): void
    {
        $this->hub->fireEvent(
            eventType:  'item.updated',
            entityType: 'item',
            entityId:   $product->id,
            payload:    [
                'data' => $product->toArray()
            ],
        );
    }

    public function deleted(Product $product): void
    {
        $this->hub->fireEvent(
            eventType:  'item.deleted',
            entityType: 'item',
            entityId:   $product->id,
            payload:    ['id' => $product->id],
        );
    }
}