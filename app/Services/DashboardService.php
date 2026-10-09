<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;

class DashboardService
{
    /**
     * Painel do administrador (RF-023):
     * totais gerais + consultas por status + últimas consultas.
     */
    public function adminDashboard(): array
    {
        $today = Carbon::today();

        return [
            'totals' => [
                'doctors' => Doctor::count(),
                'doctors_active' => Doctor::where('is_active', true)->count(),
                'patients' => Patient::count(),
                'users' => User::count(),
            ],
            'appointments_by_status' => Appointment::selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'today_appointments' => Appointment::with(['doctor.user', 'patient.user'])
                ->whereDate('scheduled_date', $today)
                ->whereNotIn('status', [AppointmentStatus::CANCELADA->value])
                ->orderBy('scheduled_time')
                ->limit(10)
                ->get()
                ->map(fn ($a) => $this->appointmentSummary($a)),
            'upcoming_appointments' => Appointment::with(['doctor.user', 'patient.user'])
                ->whereDate('scheduled_date', '>', $today)
                ->where('status', AppointmentStatus::AGENDADA->value)
                ->orderBy('scheduled_date')
                ->orderBy('scheduled_time')
                ->limit(5)
                ->get()
                ->map(fn ($a) => $this->appointmentSummary($a)),
        ];
    }

    /**
     * Painel do médico (RF-024):
     * agenda do dia, próximas consultas, resumo semanal.
     */
    public function doctorDashboard(User $user): array
    {
        $doctor = Doctor::where('user_id', $user->id)->firstOrFail();
        $today = Carbon::today();
        $weekStart = $today->copy()->startOfWeek();
        $weekEnd = $today->copy()->endOfWeek();

        $todayAppts = Appointment::with(['patient.user'])
            ->where('doctor_id', $doctor->id)
            ->whereDate('scheduled_date', $today)
            ->whereNotIn('status', [AppointmentStatus::CANCELADA->value])
            ->orderBy('scheduled_time')
            ->get();

        $upcoming = Appointment::with(['patient.user'])
            ->where('doctor_id', $doctor->id)
            ->whereDate('scheduled_date', '>', $today)
            ->where('status', AppointmentStatus::AGENDADA->value)
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->limit(5)
            ->get();

        $weekSummary = Appointment::where('doctor_id', $doctor->id)
            ->whereBetween('scheduled_date', [$weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $absentThisWeek = (int) ($weekSummary[AppointmentStatus::PACIENTE_AUSENTE->value] ?? 0);

        return [
            'today_appointments' => $todayAppts->map(fn ($a) => $this->appointmentSummary($a)),
            'upcoming_appointments' => $upcoming->map(fn ($a) => $this->appointmentSummary($a)),
            'week_summary' => $weekSummary,
            'absent_this_week' => $absentThisWeek,
        ];
    }

    /**
     * Painel do paciente (RF-025):
     * próximas consultas + histórico recente.
     */
    public function patientDashboard(User $user): array
    {
        $patient = Patient::where('user_id', $user->id)->firstOrFail();
        $today = Carbon::today();

        $upcoming = Appointment::with(['doctor.user'])
            ->where('patient_id', $patient->id)
            ->whereDate('scheduled_date', '>=', $today)
            ->where('status', AppointmentStatus::AGENDADA->value)
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->limit(5)
            ->get();

        $history = Appointment::with(['doctor.user'])
            ->where('patient_id', $patient->id)
            ->whereIn('status', [
                AppointmentStatus::CONCLUIDA->value,
                AppointmentStatus::CANCELADA->value,
                AppointmentStatus::PACIENTE_AUSENTE->value,
            ])
            ->orderByDesc('scheduled_date')
            ->limit(5)
            ->get();

        $totalConcluidas = Appointment::where('patient_id', $patient->id)
            ->where('status', AppointmentStatus::CONCLUIDA->value)
            ->count();

        return [
            'upcoming_appointments' => $upcoming->map(fn ($a) => $this->appointmentSummary($a)),
            'recent_history' => $history->map(fn ($a) => $this->appointmentSummary($a)),
            'total_concluded' => $totalConcluidas,
        ];
    }

    private function appointmentSummary(Appointment $a): array
    {
        return [
            'id' => $a->id,
            'scheduled_date' => $a->scheduled_date->toDateString(),
            'scheduled_time' => substr($a->scheduled_time, 0, 5),
            'status' => $a->status->value,
            'status_label' => $a->status->label(),
            'doctor_name' => $a->relationLoaded('doctor') ? ($a->doctor->user->name ?? null) : null,
            'patient_name' => $a->relationLoaded('patient') ? ($a->patient->user->name ?? null) : null,
        ];
    }
}
