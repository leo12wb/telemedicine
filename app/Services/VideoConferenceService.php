<?php

namespace App\Services;

use App\Contracts\VideoConferenceProvider;
use App\Models\Appointment;
use App\Models\SystemSetting;
use App\Services\VideoConference\DailyProvider;
use App\Services\VideoConference\JitsiProvider;
use App\Services\VideoConference\NoneProvider;

class VideoConferenceService
{
    public function provider(): VideoConferenceProvider
    {
        $type = SystemSetting::getValue('video_provider', 'none');

        return match ($type) {
            'jitsi' => new JitsiProvider(
                SystemSetting::getValue('jitsi_server_url', 'https://meet.jit.si')
            ),
            'daily' => new DailyProvider(
                SystemSetting::getValue('daily_api_key', ''),
                SystemSetting::getValue('daily_domain', ''),
            ),
            default => new NoneProvider(),
        };
    }

    public function getMeetingUrl(Appointment $appointment): ?string
    {
        $provider = $this->provider();

        if (! $provider->isEnabled()) {
            return null;
        }

        return $provider->getMeetingUrl($appointment);
    }
}
