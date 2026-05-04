<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'client_id',
        'order_id',
        'payment_received',
        'due_payment',
        'payment_method',
        'payment_note',
        'payment_date',
        'payment_status',
        'payment_status_note',
        'zborder_id',

        'zoho_id',
        'erpnext_id',
        'tally_id',
        'busy_id',
        'sap_id'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
