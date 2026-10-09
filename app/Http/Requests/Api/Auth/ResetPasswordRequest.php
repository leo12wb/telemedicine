<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'O token de redefinição é obrigatório.',
            'email.required' => 'O e-mail é obrigatório.',
            'password.required' => 'A nova senha é obrigatória.',
            'password.confirmed' => 'A confirmação de senha não confere.',
        ];
    }
}
