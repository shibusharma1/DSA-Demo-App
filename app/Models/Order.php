<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'customer_id',
        'order_no',
        'tot_amount',
        'grand_total',
        'due_amount',
        'order_note',
        'order_date',
        'due_date',
        'delivery_status',
        'zoho_id',
        'erpnext_id',
        'tally_id',
        'busy_id',
        'sap_id'
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
