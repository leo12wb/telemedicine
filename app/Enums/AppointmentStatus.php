<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case AGENDADA = 'agendada';
    case EM_ANDAMENTO = 'em_andamento';
    case CONCLUIDA = 'concluida';
    case CANCELADA = 'cancelada';
    case PACIENTE_AUSENTE = 'paciente_ausente';

    /** Estados que encerram definitivamente a consulta (imutáveis). */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::CONCLUIDA, self::CANCELADA, self::PACIENTE_AUSENTE => true,
            default => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::AGENDADA => 'Agendada',
            self::EM_ANDAMENTO => 'Em andamento',
            self::CONCLUIDA => 'Concluída',
            self::CANCELADA => 'Cancelada',
            self::PACIENTE_AUSENTE => 'Paciente ausente',
        };
    }
}
