
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartyBusy extends Model
{
    protected $table = 'parties_busy';

    protected $fillable = [
        'company_id',
        'busyparty_id',
        'company_name',
        'name',
        'phone',
        'mobile',
        'fax',
        'email',
        'address_1',
        'address_2',
        'country',
        'phonecode',
        'pan',
        'credit_days',
        'opening_balance',
        'closing_balance',
        'status',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'country' => 'integer',
        'credit_days' => 'integer',
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
    ];

    /**
     * Company that owns this BUSY party.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Country associated with this party.
     *
     * Uses the existing App\Country model.
     */
    public function countryInfo(): BelongsTo
    {
        return $this->belongsTo(
            \App\Country::class,
            'country'
        );
    }

    /**
     * Collections belonging to this BUSY party.
     */
    public function collections(): HasMany
    {
        return $this->hasMany(
            Collection::class,
            'client_id',
            'id'
        );
    }
}