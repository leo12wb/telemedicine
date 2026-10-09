<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Exceptions\AuthException;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    /**
     * Autentica o usuário e retorna o token de acesso.
     *
     * @throws AuthException
     */
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        // Resposta genérica para não revelar se o e-mail existe (RNF-003)
        if (! $user || ! Hash::check($password, $user->password)) {
            throw AuthException::invalidCredentials();
        }

        if (! $user->is_active) {
            throw AuthException::accountInactive();
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'user'  => $user,
            'token' => $token,
        ];
    }

    /**
     * Registra um novo paciente e dispara verificação de e-mail.
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'],
            'role'     => UserRole::PACIENTE,
            'is_active' => true,
        ]);

        // Dispara evento que envia o e-mail de verificação (RF-003)
        event(new Registered($user));

        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'user'  => $user,
            'token' => $token,
        ];
    }

    /**
     * Revoga o token atual do usuário (logout).
     */
    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        // TransientToken (usado em testes via actingAs) não persiste no banco.
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }

    /**
     * Envia o link de recuperação de senha.
     * Retorna status genérico para não revelar se o e-mail existe (RNF-003).
     */
    public function sendPasswordResetLink(string $email): void
    {
        Password::sendResetLink(['email' => $email]);
    }

    /**
     * Redefine a senha do usuário via token.
     *
     * @throws \Exception
     */
    public function resetPassword(string $token, string $email, string $password): void
    {
        $status = Password::reset(
            ['email' => $email, 'password' => $password, 'password_confirmation' => $password, 'token' => $token],
            function (User $user, string $password) {
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw new \Exception(__($status), 422);
        }
    }
}
