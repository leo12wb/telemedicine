<?php

namespace App\Http\Requests\Api\Specialty;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSpecialtyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $id = $this->route('specialty')->id;

        return [
            'name'        => ['sometimes', 'string', 'max:100', "unique:specialties,name,{$id}"],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'is_active'   => ['sometimes', 'boolean'],
        ];
    }
}
