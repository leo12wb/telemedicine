<?php

namespace App\Http\Requests\Api\Appointment;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class FinishAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Appointment $appointment */
        $appointment = $this->route('appointment');

        return $appointment->doctor->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'outcome' => ['required', new Enum(AppointmentStatus::class), 'in:concluida,paciente_ausente'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
