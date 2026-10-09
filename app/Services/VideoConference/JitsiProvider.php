<?php

namespace App\Services\VideoConference;

use App\Contracts\VideoConferenceProvider;
use App\Models\Appointment;

class JitsiProvider implements VideoConferenceProvider
{
    public function __construct(private readonly string $serverUrl) {}

    public function isEnabled(): bool
    {
        return true;
    }

    public function getMeetingUrl(Appointment $appointment): string
    {
        $roomName = 'consulta-'.substr(str_replace('-', '', $appointment->id), 0, 12);

        return rtrim($this->serverUrl, '/').'/'.$roomName;
    }
}
