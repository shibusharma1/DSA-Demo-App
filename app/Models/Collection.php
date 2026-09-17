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
        // 'unique_id',
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
    ];

    /**
     * Company that owns this collection.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(
            Company::class,
            'company_id'
        );
    }

    /**
     * Existing DSA client associated with the collection.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(
            Client::class,
            'client_id'
        );
    }

    /**
     * BUSY party associated with the collection.
     *
     * This relationship uses the same client_id column,
     * but references parties_busy instead of clients.
     */
    public function busyParty(): BelongsTo
    {
        return $this->belongsTo(
            PartyBusy::class,
            'client_id',
            'id'
        );
    }

    /**
     * Order associated with the collection.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(
            Order::class,
            'order_id'
        );
    }

    /**
     * Employee relationship.
     *
     * Supports employee_type values such as
     * App\Models\User or App\Models\Employee.
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
     * Bank associated with the collection.
     */
    public function bank(): BelongsTo
    {
        return $this->belongsTo(
            Bank::class,
            'bank_id'
        );
    }

    /**
     * Collection type associated with the collection.
     */
    public function collectionType(): BelongsTo
    {
        return $this->belongsTo(
            CollectionType::class,
            'collection_types_id'
        );
    }
}