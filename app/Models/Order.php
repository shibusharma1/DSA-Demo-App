<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'company_id',
        'client_id',
        'busyorder_id',
        'order_no',
        'order_date',
        'order_to_id',
        'order_notes',
        'sub_total',
        'discount',
        'total_tax',
        'delivery_charge',
        'grand_total',
        'status',
        'busy_sync_status',
        'busy_sync_message',
        'busy_synced_at',
    ];

    protected $casts = [
        'order_date' => 'date',
        'busy_synced_at' => 'datetime',

        'sub_total' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'delivery_charge' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Customer
    |--------------------------------------------------------------------------
    */

    public function client(): BelongsTo
    {
        return $this->belongsTo(
            PartyBusy::class,
            'client_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Order Details
    |--------------------------------------------------------------------------
    */

    public function details(): HasMany
    {
        return $this->hasMany(
            OrderDetail::class,
            'order_id'
        )->orderBy('sort_order');
    }
    public function items(): HasMany
{
    return $this->hasMany(
        OrderDetail::class,
        'order_id'
    )->orderBy('sort_order');
}
}