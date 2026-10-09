<?php

namespace App\Http\Requests\Api\Availability;

use App\Models\Doctor;
use Illuminate\Foundation\Http\FormRequest;

class StoreBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Doctor $doctor */
        $doctor = $this->route('doctor');
        $user = $this->user();

        return $user->isAdmin() || $doctor->user_id === $user->id;
    }

    public function rules(): array
    {
        return [
            'block_date' => ['required', 'date', 'date_format:Y-m-d'],
            'block_start' => ['nullable', 'date_format:H:i', 'required_with:block_end'],
            'block_end' => ['nullable', 'date_format:H:i', 'after:block_start', 'required_with:block_start'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
