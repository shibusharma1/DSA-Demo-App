<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDetail extends Model
{
    protected $table = 'order_details';

    protected $fillable = [
        'order_id',
        'product_id',
        'unit_id',
        'tax_id',
        'rate',
        'quantity',
        'discount',
        'discount_type',
        'discount_amount',
        'applied_rate',
        'tax_rate',
        'tax_amount',
        'taxable_amount',
        'amount',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'quantity' => 'decimal:4',
        'discount' => 'decimal:4',
        'discount_amount' => 'decimal:2',
        'applied_rate' => 'decimal:4',
        'tax_rate' => 'decimal:3',
        'tax_amount' => 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Order
    |--------------------------------------------------------------------------
    */

    public function order(): BelongsTo
    {
        return $this->belongsTo(
            Order::class,
            'order_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Product
    |--------------------------------------------------------------------------
    */

    public function product(): BelongsTo
    {
        return $this->belongsTo(
            Product::class,
            'product_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Unit
    |--------------------------------------------------------------------------
    */

    public function unit(): BelongsTo
    {
        return $this->belongsTo(
            UnitType::class,
            'unit_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Tax
    |--------------------------------------------------------------------------
    */

    public function tax(): BelongsTo
    {
        return $this->belongsTo(
            TaxType::class,
            'tax_id'
        );
    }
}