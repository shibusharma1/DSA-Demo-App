<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'contact_name',
        'email',
        'company_name',
        'phone',
        'zb_id'
    ];
}
