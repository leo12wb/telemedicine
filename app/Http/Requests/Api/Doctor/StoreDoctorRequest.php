<?php

namespace App\Http\Requests\Api\Doctor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreDoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            // Dados do usuário
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(8)->mixedCase()->numbers()->symbols()],
            // Dados do médico
            'crm'      => ['required', 'string', 'max:20'],
            'crm_uf'   => ['required', 'string', 'size:2'],
            'phone'    => ['nullable', 'string', 'max:20'],
            'bio'      => ['nullable', 'string', 'max:2000'],
            // Especialidades
            'specialty_ids'   => ['nullable', 'array'],
            'specialty_ids.*' => ['uuid', 'exists:specialties,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ($this->filled(['crm', 'crm_uf'])) {
                $exists = \App\Models\Doctor::where('crm', $this->crm)
                    ->where('crm_uf', strtoupper($this->crm_uf))
                    ->exists();

                if ($exists) {
                    $v->errors()->add('crm', 'CRM já cadastrado para esta UF.');
                }
            }
        });
    }
}
