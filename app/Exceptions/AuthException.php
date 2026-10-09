<?php

namespace App\Exceptions;

use Exception;

class AuthException extends Exception
{
    public static function invalidCredentials(): self
    {
        return new self('Credenciais inválidas.', 401);
    }

    public static function accountInactive(): self
    {
        return new self('Conta desativada. Entre em contato com o suporte.', 403);
    }

    public static function emailNotVerified(): self
    {
        return new self('E-mail não verificado. Verifique sua caixa de entrada.', 403);
    }
}
