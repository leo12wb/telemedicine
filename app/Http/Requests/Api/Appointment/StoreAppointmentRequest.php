<?php

namespace App\Http\Requests\Api\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Paciente agenda para si; admin pode agendar para qualquer paciente
        return $this->user()->role->value === 'paciente' || $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'doctor_id'      => ['required', 'uuid', 'exists:doctors,id'],
            'scheduled_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'scheduled_time' => ['required', 'date_format:H:i'],
        ];
    }
}
