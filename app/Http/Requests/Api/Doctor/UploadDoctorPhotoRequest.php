<?php

namespace App\Http\Requests\Api\Doctor;

use App\Models\Doctor;
use Illuminate\Foundation\Http\FormRequest;

class UploadDoctorPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Doctor $doctor */
        $doctor = $this->route('doctor');
        $user = $this->user();

        return $user->isAdmin() || $user->id === $doctor->user_id;
    }

    public function rules(): array
    {
        return [
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
