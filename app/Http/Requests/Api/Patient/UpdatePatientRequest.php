<?php

namespace App\Http\Requests\Api\Patient;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        $patient = $this->route('patient');

        return $this->user()->isAdmin() || $this->user()->id === $patient->user_id;
    }

    public function rules(): array
    {
        $patientId = $this->route('patient')->id;

        return [
            'cpf'                     => ['sometimes', 'nullable', 'string', 'max:14', "unique:patients,cpf,{$patientId}"],
            'birth_date'              => ['sometimes', 'nullable', 'date', 'before:today'],
            'phone'                   => ['sometimes', 'nullable', 'string', 'max:20'],
            'health_insurance'        => ['sometimes', 'nullable', 'string', 'max:100'],
            'health_insurance_number' => ['sometimes', 'nullable', 'string', 'max:50'],
        ];
    }
}
