<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'product_name',
        'product_code',
        'mrp',
        'd_price',
        'r_price',
        'unit_name',
        'inventory_available_quantity',
        'status',
        // 'zbproduct_id',
        'zoho_id',
        'erpnext_id',
        'tally_id',
        'busy_id',
        'sap_id'
    ];
}
