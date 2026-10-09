<?php

namespace App\Http\Requests\Api\Patient;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StorePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name'                    => ['required', 'string', 'max:255'],
            'email'                   => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'                => ['required', Password::min(8)->mixedCase()->numbers()->symbols()],
            'cpf'                     => ['nullable', 'string', 'max:14', 'unique:patients,cpf'],
            'birth_date'              => ['nullable', 'date', 'before:today'],
            'phone'                   => ['nullable', 'string', 'max:20'],
            'health_insurance'        => ['nullable', 'string', 'max:100'],
            'health_insurance_number' => ['nullable', 'string', 'max:50'],
        ];
    }
}
