<?php

namespace App\Services\VideoConference;

use App\Contracts\VideoConferenceProvider;
use App\Models\Appointment;

class NoneProvider implements VideoConferenceProvider
{
    public function isEnabled(): bool
    {
        return false;
    }

    public function getMeetingUrl(Appointment $appointment): string
    {
        throw new \LogicException('NoneProvider does not provide meeting URLs.');
    }
}
