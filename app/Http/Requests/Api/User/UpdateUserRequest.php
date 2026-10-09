<?php

namespace App\Http\Requests\Api\User;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $this->user()->isAdmin() || $this->user()->id === $user->id;
    }

    public function rules(): array
    {
        $isAdmin = $this->user()->isAdmin();

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,'.$this->route('user')->id],
            'password' => ['sometimes', Password::min(8)->mixedCase()->numbers()->symbols()],
            // Somente admin pode alterar role e status
            'role' => $isAdmin ? ['sometimes', new Enum(UserRole::class)] : ['prohibited'],
            'is_active' => $isAdmin ? ['sometimes', 'boolean'] : ['prohibited'],
        ];
    }
}
