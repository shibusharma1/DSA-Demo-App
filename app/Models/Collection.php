<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Collection extends Model
{
    use SoftDeletes;

    protected $table = 'collections';

    protected $fillable = [
        'company_id',
        'client_id',
        'order_id',
        'employee_id',
        'employee_type',

        'payment_received',
        'due_payment',

        'payment_method',
        'payment_note',

        'status',

        'image',
        'image_path',

        'payment_date',
        'next_date',

        'bank_id',

        'cheque_no',
        'cheque_date',

        'payment_status',
        'payment_status_note',

        'collection_types_id',

        'include_in_credit',

        'erpnxtpayment_id',

        'busycollection_id',
        'busy_voucher_no',
        'busy_voucher_series',
        'busy_sync_status',
        'busy_sync_message',
        'busy_synced_at',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'order_id' => 'integer',
        'employee_id' => 'integer',
        'bank_id' => 'integer',
        'collection_types_id' => 'integer',

        'payment_received' => 'decimal:2',
        'due_payment' => 'decimal:2',

        'payment_date' => 'date',
        'next_date' => 'date',
        'cheque_date' => 'date',

        'include_in_credit' => 'boolean',

        'busy_synced_at' => 'datetime',
    ];

    /**
     * Company.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(
            Company::class,
            'company_id'
        );
    }

    /**
     * Existing DSA client.
     *
     * IMPORTANT:
     * client_id belongs to the existing clients table.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(
            PartyBusy::class,
            'client_id'
        );
    }

    /**
     * Order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(
            Order::class,
            'order_id'
        );
    }

    /**
     * Employee.
     */
    public function employee(): MorphTo
    {
        return $this->morphTo(
            'employee',
            'employee_type',
            'employee_id'
        );
    }

    /**
     * Bank.
     */
    // public function bank(): BelongsTo
    // {
    //     return $this->belongsTo(
    //         Bank::class,
    //         'bank_id'
    //     );
    // }

    /**
     * Collection Type.
     */
    // public function collectionType(): BelongsTo
    // {
    //     return $this->belongsTo(
    //         CollectionType::class,
    //         'collection_types_id'
    //     );
    // }

    /**
     * Check whether collection is synced to BUSY.
     */
    public function isSyncedToBusy(): bool
    {
        return !empty($this->busycollection_id)
            && $this->busy_sync_status === 'synced';
    }
}