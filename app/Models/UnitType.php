
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitType extends Model
{
    protected $table = 'unit_types';

    protected $fillable = [
        'company_id',
        'busyunit_id',
        'name',
        'symbol',
        'status',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'unit');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}