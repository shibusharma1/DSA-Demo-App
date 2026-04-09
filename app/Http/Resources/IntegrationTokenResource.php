<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IntegrationTokenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'company_id'   => $this->company_id,
            'user_id'      => $this->user_id,
            'provider'     => $this->provider,
            'service_type' => $this->service_type,

            'access_token'  => $this->access_token,
            'refresh_token' => $this->refresh_token,
            'expires_in'    => $this->expires_in,
            'expires_at'    => $this->access_token_expires_at,

            'api_base_url' => $this->api_base_url,

            'settings'     => $this->settings,
            'token_data'   => $this->token_response,
        ];
    }
}