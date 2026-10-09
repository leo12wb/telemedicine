<?php

namespace App\Http\Requests\Api\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'video_provider' => ['required', 'in:none,jitsi,daily'],
            'jitsi_server_url' => [
                'nullable',
                'url',
                'required_if:video_provider,jitsi',
            ],
            'daily_api_key' => [
                'nullable',
                'string',
                'required_if:video_provider,daily',
            ],
            'daily_domain' => [
                'nullable',
                'string',
                'alpha_dash',
                'required_if:video_provider,daily',
            ],
        ];
    }
}
