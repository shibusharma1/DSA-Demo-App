<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IntegrationTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // secured via middleware
    }

    public function rules(): array
    {
        return [
            'company_id'   => ['required', 'integer'],
            'user_id'      => ['required', 'integer'],
            'provider'     => ['required', 'string', 'max:50'],
            'service_type' => ['nullable', 'string', 'max:50'],
        ];
    }
}