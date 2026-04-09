<?php
// app/Models/IntegrationAccount.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IntegrationAccount extends Model
{
    use HasFactory;

    protected $table = 'integration_accounts';

    protected $fillable = [
        'company_id',
        'user_id',
        'provider',
        'service_type',
        'organization_id',
        'account_id',
        'client_id',
        'client_secret',
        'auth_url',
        'token_url',
        'redirect_uri',
        'api_base_url',
        'access_token',
        'refresh_token',
        'id_token',
        'token_type',
        'scope',
        'expires_in',
        'access_token_expires_at',
        'refresh_token_expires_at',
        'revoked_at',
        'api_key',
        'api_secret',
        'settings',
        'token_response',
        'meta',
        'is_active',
        'last_synced_at',
    ];

    protected $casts = [
        'settings'                 => 'array',
        'token_response'           => 'array',
        'meta'                     => 'array',
        'is_active'                => 'boolean',
        'access_token_expires_at'  => 'datetime',
        'refresh_token_expires_at' => 'datetime',
        'revoked_at'               => 'datetime',
        'last_synced_at'           => 'datetime',
    ];

    public function isTokenExpired(): bool
    {
        if (!$this->access_token_expires_at) {
            return true;
        }

        return now()->gte($this->access_token_expires_at);
    }
}
