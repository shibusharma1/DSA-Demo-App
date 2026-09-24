<?php

namespace App\Models;

// use App\Country;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartyBusy extends Model
{
    protected $table = 'parties_busy';

    protected $fillable = [
        'company_id',
        'busyparty_id',

        'company_name',
        'name',
        'alias',
        'print_name',
        'parent_group',
        'bill_by_bill_balancing',

        'phone',
        'mobile',
        'whatsapp_no',
        'fax',
        'email',
        'contact',
        'cont_dept_name',

        'address_1',
        'address_2',
        'address_3',
        'address_4',

        'country',
        'phonecode',
        'state',
        'city',
        'area',

        'pan',
        'gst_no',
        'tin_no',
        'it_ward',
        'st37',

        'account_no',
        'bank_name',
        'ifsc_code',
        'swift_code',

        'c3',
        'c4',
        'c5',
        'c8',

        'tmp_master_code',
        'tmp_code',
        'tmp_parent_group_code',

        'supplier_type',
        'credit_days_sale',
        'credit_days_purchase',

        'price_level',
        'price_level_purchase',

        'tax_type',
        'type_of_dealer_gst',

        'cheque_print_name',
        'reverse_charge_type',
        'input_type',

        'opening_balance',
        'closing_balance',

        'status',

        'busy_sync_status',
        'busy_sync_message',
        'busy_synced_at',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'country' => 'integer',

        'bill_by_bill_balancing' => 'boolean',

        'credit_days_sale' => 'integer',
        'credit_days_purchase' => 'integer',

        'opening_balance' => 'decimal:4',
        'closing_balance' => 'decimal:4',

        'busy_synced_at' => 'datetime',
    ];


    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    // public function countryInfo(): BelongsTo
    // {
    //     return $this->belongsTo(\App\Country::class, 'country');
    // }

    // public function collections(): HasMany
    // {
    //     return $this->hasMany(Collection::class,'client_id','id');
    // }
    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class, 'busy_party_id', 'id');
    }
    
    public function isSyncedToBusy(): bool
    {
        return !empty($this->busyparty_id)  && $this->busy_sync_status === 'Synced';
    }
}
