<?php

namespace App\Http\Requests\Api\Availability;

use App\Models\Doctor;
use Illuminate\Foundation\Http\FormRequest;

class UpdateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Doctor $doctor */
        $doctor = $this->route('doctor');
        $user   = $this->user();

        return $user->isAdmin() || $doctor->user_id === $user->id;
    }

    public function rules(): array
    {
        return [
            'day_of_week'           => ['sometimes', 'integer', 'between:0,6'],
            'start_time'            => ['sometimes', 'date_format:H:i'],
            'end_time'              => ['sometimes', 'date_format:H:i', 'after:start_time'],
            'slot_duration_minutes' => ['sometimes', 'integer', 'in:15,20,30,45,60'],
            'is_active'             => ['sometimes', 'boolean'],
        ];
    }
}
