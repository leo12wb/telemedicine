<?php

namespace App\Http\Requests\Api\Appointment;

use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;

class RescheduleAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Appointment $appointment */
        $appointment = $this->route('appointment');
        $user = $this->user();

        return $user->isAdmin() || $appointment->patient->user_id === $user->id;
    }

    public function rules(): array
    {
        return [
            'scheduled_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'scheduled_time' => ['required', 'date_format:H:i'],
        ];
    }
}
