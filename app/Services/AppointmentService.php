<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\AppointmentBooked;
use App\Notifications\AppointmentCancelled;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    /**
     * Lista consultas filtradas conforme o papel do usuário.
     * Admin → todas; Médico → suas consultas; Paciente → suas consultas.
     */
    public function list(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Appointment::with(['doctor.user', 'patient.user'])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time');

        if ($user->isAdmin()) {
            // sem restrição adicional
        } elseif ($user->role->value === 'medico') {
            $doctor = Doctor::where('user_id', $user->id)->firstOrFail();
            $query->where('doctor_id', $doctor->id);
        } else {
            $patient = Patient::where('user_id', $user->id)->firstOrFail();
            $query->where('patient_id', $patient->id);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['date'])) {
            $query->whereDate('scheduled_date', $filters['date']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('scheduled_date', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('scheduled_date', '<=', $filters['to']);
        }

        return $query->paginate(15);
    }

    /**
     * Agenda uma consulta para o paciente.
     * Garante atomicidade contra double-booking via SELECT ... FOR UPDATE.
     */
    public function create(array $data, Patient $patient): Appointment
    {
        // Normaliza HH:MM → HH:MM:00 para consistência com o banco
        $data['scheduled_time'] = strlen($data['scheduled_time']) === 5
            ? $data['scheduled_time'].':00'
            : $data['scheduled_time'];

        return DB::transaction(function () use ($data, $patient) {
            // Verifica conflito de horário (SELECT FOR UPDATE evita race condition)
            $conflict = Appointment::where('doctor_id', $data['doctor_id'])
                ->whereDate('scheduled_date', $data['scheduled_date'])
                ->where('scheduled_time', $data['scheduled_time'])
                ->whereNotIn('status', [AppointmentStatus::CANCELADA->value])
                ->lockForUpdate()
                ->exists();

            if ($conflict) {
                throw ValidationException::withMessages([
                    'scheduled_time' => 'Este horário já está ocupado. Escolha outro.',
                ]);
            }

            // Verifica antecedência mínima de 2 horas (RN-013)
            $scheduledAt = Carbon::parse(
                $data['scheduled_date'].' '.$data['scheduled_time']
            );

            if ($scheduledAt->diffInMinutes(now(), false) > -120) {
                throw ValidationException::withMessages([
                    'scheduled_time' => 'O agendamento deve ser feito com no mínimo 2 horas de antecedência.',
                ]);
            }

            $doctor = Doctor::findOrFail($data['doctor_id']);

            $appointment = Appointment::create([
                'doctor_id' => $data['doctor_id'],
                'patient_id' => $patient->id,
                'scheduled_date' => $data['scheduled_date'],
                'scheduled_time' => $data['scheduled_time'],
                'duration_minutes' => $doctor->schedules()
                    ->where('is_active', true)
                    ->value('slot_duration_minutes') ?? 30,
                'status' => AppointmentStatus::AGENDADA,
            ]);

            $appointment->load('doctor.user', 'patient.user');

            // Notificações (queued)
            $appointment->patient->user->notify(new AppointmentBooked($appointment));
            $appointment->doctor->user->notify(new AppointmentBooked($appointment));

            return $appointment;
        });
    }

    /**
     * Cancela uma consulta.
     * Paciente: apenas até 24h antes (RN-014).
     * Médico e Admin: qualquer momento antes de concluída.
     */
    public function cancel(Appointment $appointment, User $actor, ?string $reason = null): Appointment
    {
        if ($appointment->status->isTerminal()) {
            throw ValidationException::withMessages([
                'status' => 'Consulta já encerrada não pode ser cancelada.',
            ]);
        }

        // Prazo para cancelamento pelo paciente: 24h antes (RN-014)
        if ($actor->role->value === 'paciente') {
            $scheduledAt = Carbon::parse(
                $appointment->scheduled_date->toDateString().' '.$appointment->scheduled_time
            );

            if ($scheduledAt->diffInHours(now(), false) > -24) {
                throw ValidationException::withMessages([
                    'scheduled_time' => 'O prazo para cancelamento pelo paciente (24h antes) já expirou.',
                ]);
            }
        }

        $appointment->update([
            'status' => AppointmentStatus::CANCELADA,
            'cancelled_by' => $actor->id,
            'cancellation_reason' => $reason,
        ]);

        $appointment->load('doctor.user', 'patient.user');

        // Notificações (queued)
        $appointment->patient->user->notify(new AppointmentCancelled($appointment));
        $appointment->doctor->user->notify(new AppointmentCancelled($appointment));

        return $appointment->fresh(['doctor.user', 'patient.user']);
    }

    /**
     * Reagenda: cancela a consulta atual e cria uma nova.
     */
    public function reschedule(Appointment $appointment, array $data, Patient $patient): Appointment
    {
        if ($appointment->status->isTerminal()) {
            throw ValidationException::withMessages([
                'status' => 'Consulta já encerrada não pode ser reagendada.',
            ]);
        }

        return DB::transaction(function () use ($appointment, $data, $patient) {
            $this->cancel($appointment, $patient->user, 'Reagendamento solicitado pelo paciente.');

            return $this->create([
                'doctor_id' => $appointment->doctor_id,
                'scheduled_date' => $data['scheduled_date'],
                'scheduled_time' => $data['scheduled_time'],
            ], $patient);
        });
    }

    /**
     * Inicia a consulta (agendada → em_andamento).
     * Apenas o médico responsável pode iniciar.
     */
    public function start(Appointment $appointment): Appointment
    {
        if ($appointment->status !== AppointmentStatus::AGENDADA) {
            throw ValidationException::withMessages([
                'status' => 'Apenas consultas agendadas podem ser iniciadas.',
            ]);
        }

        $appointment->update([
            'status' => AppointmentStatus::EM_ANDAMENTO,
            'started_at' => now(),
        ]);

        return $appointment->fresh(['doctor.user', 'patient.user']);
    }

    /**
     * Encerra a consulta (em_andamento → concluida | paciente_ausente).
     */
    public function finish(Appointment $appointment, AppointmentStatus $outcome, ?string $notes = null): Appointment
    {
        if ($appointment->status !== AppointmentStatus::EM_ANDAMENTO) {
            throw ValidationException::withMessages([
                'status' => 'Apenas consultas em andamento podem ser encerradas.',
            ]);
        }

        if (! in_array($outcome, [AppointmentStatus::CONCLUIDA, AppointmentStatus::PACIENTE_AUSENTE])) {
            throw ValidationException::withMessages([
                'outcome' => 'Desfecho inválido.',
            ]);
        }

        $appointment->update([
            'status' => $outcome,
            'ended_at' => now(),
            'notes' => $notes ?? $appointment->notes,
        ]);

        return $appointment->fresh(['doctor.user', 'patient.user']);
    }

    /**
     * Atualiza as anotações clínicas da consulta.
     * Permitido apenas enquanto a consulta não estiver em estado terminal (RN-017).
     */
    public function updateNotes(Appointment $appointment, string $notes): Appointment
    {
        if ($appointment->status->isTerminal()) {
            throw ValidationException::withMessages([
                'notes' => 'Anotações clínicas não podem ser editadas após o encerramento da consulta.',
            ]);
        }

        $appointment->update(['notes' => $notes]);

        return $appointment->fresh(['doctor.user', 'patient.user']);
    }
}
