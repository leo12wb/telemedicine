<?php

namespace App\Http\Requests\Api\Doctor;

use App\Models\Doctor;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        $doctor = $this->route('doctor');

        return $this->user()->isAdmin() || $this->user()->id === $doctor->user_id;
    }

    public function rules(): array
    {
        $isAdmin = $this->user()->isAdmin();
        $doctorId = $this->route('doctor')->id;

        return [
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:2000'],
            // Somente admin altera CRM, UF e especialidades
            'crm' => $isAdmin ? ['sometimes', 'string', 'max:20'] : ['prohibited'],
            'crm_uf' => $isAdmin ? ['sometimes', 'string', 'size:2'] : ['prohibited'],
            'specialty_ids' => $isAdmin ? ['sometimes', 'nullable', 'array'] : ['prohibited'],
            'specialty_ids.*' => ['uuid', 'exists:specialties,id'],
            'is_active' => $isAdmin ? ['sometimes', 'boolean'] : ['prohibited'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ($this->filled(['crm', 'crm_uf'])) {
                $doctorId = $this->route('doctor')->id;
                $exists = Doctor::where('crm', $this->crm)
                    ->where('crm_uf', strtoupper($this->crm_uf))
                    ->where('id', '!=', $doctorId)
                    ->exists();

                if ($exists) {
                    $v->errors()->add('crm', 'CRM já cadastrado para esta UF.');
                }
            }
        });
    }
}
