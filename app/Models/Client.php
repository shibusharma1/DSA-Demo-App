<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'client_code',
        'name',
        'company_name',
        'email',
        'phone',
        'mobile',
        'address_1',
        'address_2',
        'location',
        'pan',
        'website',
        'opening_balance',
        'closing_balance',
        'due_amount',
        'credit_limit',
        'credit_days',
        'status',
        // 'zbcustomer_id',
        'zoho_id',
        'erpnext_id',
        'tally_id',
        'busy_id',
        'sap_id'
    ];
}
