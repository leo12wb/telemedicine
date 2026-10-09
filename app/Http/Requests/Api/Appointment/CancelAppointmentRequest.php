<?php

namespace App\Http\Requests\Api\Appointment;

use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;

class CancelAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Appointment $appointment */
        $appointment = $this->route('appointment');
        $user        = $this->user();

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->role->value === 'medico') {
            return $appointment->doctor->user_id === $user->id;
        }

        if ($user->role->value === 'paciente') {
            return $appointment->patient->user_id === $user->id;
        }

        return false;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
