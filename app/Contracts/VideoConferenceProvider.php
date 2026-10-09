<?php

namespace App\Contracts;

use App\Models\Appointment;

interface VideoConferenceProvider
{
    public function isEnabled(): bool;

    public function getMeetingUrl(Appointment $appointment): string;
}
