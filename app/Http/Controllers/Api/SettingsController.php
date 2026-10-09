<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Settings\UpdateSettingsRequest;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    /** GET /api/v1/settings */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', SystemSetting::class);

        $settings = SystemSetting::pluck('value', 'key');

        return response()->json(['data' => $settings]);
    }

    /** PUT /api/v1/settings */
    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Persiste apenas as chaves relevantes ao provider escolhido
        $toSave = ['video_provider' => $data['video_provider']];

        if ($data['video_provider'] === 'jitsi') {
            $toSave['jitsi_server_url'] = $data['jitsi_server_url'];
        }

        if ($data['video_provider'] === 'daily') {
            $toSave['daily_api_key'] = $data['daily_api_key'];
            $toSave['daily_domain'] = $data['daily_domain'];
        }

        SystemSetting::setMany($toSave);

        return response()->json(['data' => SystemSetting::pluck('value', 'key')]);
    }
}
