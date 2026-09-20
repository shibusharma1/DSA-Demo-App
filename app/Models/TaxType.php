<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxType extends Model
{
    protected $table = 'tax_types';

    protected $fillable = [
        'company_id',
        'busytax_id',
        'name',
        'display_name',
        'percent',
        'default_flag',
        'status',
    ];

    protected $casts = [
        'percent' => 'decimal:4',
        'default_flag' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}