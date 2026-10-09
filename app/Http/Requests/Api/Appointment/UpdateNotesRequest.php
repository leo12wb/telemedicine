<?php

namespace App\Http\Requests\Api\Appointment;

use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNotesRequest extends FormRequest
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
            'notes' => ['required', 'string'],
        ];
    }
}
