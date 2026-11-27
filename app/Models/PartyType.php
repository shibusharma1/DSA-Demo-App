<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartyType extends Model
{
    use HasFactory;

    protected $table = 'partytypes';

    protected $fillable = [
        'company_id',
        'name',
        'short_name',
        'parent_id',
        'status',
        'allow_salesman',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'parent_id' => 'integer',
        'status' => 'boolean',
        'allow_salesman' => 'boolean',
    ];

    /**
     * Get the parent party type.
     */
    public function parent()
    {
        return $this->belongsTo(PartyType::class, 'parent_id');
    }

    /**
     * Get the child party types.
     */
    public function children()
    {
        return $this->hasMany(PartyType::class, 'parent_id');
    }
}