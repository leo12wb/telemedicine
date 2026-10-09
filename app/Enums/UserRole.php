<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case MEDICO = 'medico';
    case PACIENTE = 'paciente';

    public function label(): string
    {
        return match($this) {
            self::ADMIN => 'Administrador',
            self::MEDICO => 'Médico',
            self::PACIENTE => 'Paciente',
        };
    }
}
