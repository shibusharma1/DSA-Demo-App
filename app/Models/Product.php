<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $table = 'products';

    protected $fillable = [
        'company_id',
        'busyproduct_id',
        'product_name',
        'product_code',
        'category_id',
        'brand',
        'unit',
        'mrp',
        'details',
        'short_desc',
        'status',
    ];

    protected $casts = [
        'mrp' => 'decimal:4',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    public function unitType(): BelongsTo
    {
        return $this->belongsTo(UnitType::class, 'unit');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}