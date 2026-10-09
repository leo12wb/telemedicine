<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorBlock;
use App\Models\DoctorSchedule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class AvailabilityService
{
    // ─── Schedules ────────────────────────────────────────────────────────────

    public function listSchedules(Doctor $doctor): Collection
    {
        return $doctor->schedules()->orderBy('day_of_week')->orderBy('start_time')->get();
    }

    public function createSchedule(Doctor $doctor, array $data): DoctorSchedule
    {
        return $doctor->schedules()->create([
            'day_of_week' => $data['day_of_week'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'slot_duration_minutes' => $data['slot_duration_minutes'],
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function updateSchedule(DoctorSchedule $schedule, array $data): DoctorSchedule
    {
        $schedule->update(array_filter($data, fn ($v) => $v !== null));

        return $schedule->fresh();
    }

    public function deleteSchedule(DoctorSchedule $schedule): void
    {
        $schedule->delete();
    }

    // ─── Blocks ───────────────────────────────────────────────────────────────

    public function listBlocks(Doctor $doctor, ?string $from = null, ?string $to = null): Collection
    {
        $query = $doctor->blocks()->orderBy('block_date')->orderBy('block_start');

        if ($from) {
            $query->whereDate('block_date', '>=', $from);
        }

        if ($to) {
            $query->whereDate('block_date', '<=', $to);
        }

        return $query->get();
    }

    public function createBlock(Doctor $doctor, array $data): DoctorBlock
    {
        return $doctor->blocks()->create([
            'block_date' => $data['block_date'],
            'block_start' => $data['block_start'] ?? null,
            'block_end' => $data['block_end'] ?? null,
            'reason' => $data['reason'] ?? null,
        ]);
    }

    public function deleteBlock(DoctorBlock $block): void
    {
        $block->delete();
    }

    // ─── Available Slots ──────────────────────────────────────────────────────

    /**
     * Retorna os slots disponíveis de um médico em uma data específica.
     *
     * Algoritmo:
     * 1. Determina o dia da semana da data solicitada.
     * 2. Busca os horários recorrentes ativos para esse dia.
     * 3. Gera todos os slots de cada horário.
     * 4. Remove slots cobertos por bloqueios do dia.
     * 5. Remove slots já ocupados por consultas agendadas (quando o módulo de
     *    agendamentos for implementado, o check é feito via Appointment model).
     *
     * @return array<int, array{time: string, schedule_id: string}>
     */
    public function getAvailableSlots(Doctor $doctor, string $date): array
    {
        $carbon = Carbon::parse($date);
        $dayOfWeek = (int) $carbon->dayOfWeek; // 0=Dom ... 6=Sáb

        // 1. Horários ativos para o dia
        $schedules = $doctor->schedules()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->get();

        if ($schedules->isEmpty()) {
            return [];
        }

        // 2. Bloqueios do dia
        $blocks = $doctor->blocks()
            ->whereDate('block_date', $date)
            ->get();

        // Verifica bloqueio de dia inteiro
        $hasAllDayBlock = $blocks->contains(fn ($b) => is_null($b->block_start));

        if ($hasAllDayBlock) {
            return [];
        }

        // 3. Gera todos os slots possíveis
        $slots = [];

        foreach ($schedules as $schedule) {
            $current = Carbon::createFromFormat('H:i:s', $schedule->start_time);
            $end = Carbon::createFromFormat('H:i:s', $schedule->end_time);
            $step = $schedule->slot_duration_minutes;

            while ($current->copy()->addMinutes($step)->lte($end)) {
                $slots[] = [
                    'time' => $current->format('H:i'),
                    'schedule_id' => $schedule->id,
                ];
                $current->addMinutes($step);
            }
        }

        // 4. Remove slots bloqueados (bloqueios parciais por horário)
        $partialBlocks = $blocks->filter(fn ($b) => ! is_null($b->block_start));

        if ($partialBlocks->isNotEmpty()) {
            $slots = array_values(array_filter($slots, function ($slot) use ($partialBlocks) {
                $slotTime = Carbon::createFromFormat('H:i', $slot['time']);

                foreach ($partialBlocks as $block) {
                    $blockStart = Carbon::createFromFormat('H:i:s', $block->block_start);
                    $blockEnd = Carbon::createFromFormat('H:i:s', $block->block_end);

                    if ($slotTime->gte($blockStart) && $slotTime->lt($blockEnd)) {
                        return false;
                    }
                }

                return true;
            }));
        }

        // 5. Remove slots com consultas já agendadas ou em andamento (RF-016 / RN-012)
        $bookedTimes = Appointment::where('doctor_id', $doctor->id)
            ->whereDate('scheduled_date', $date)
            ->whereNotIn('status', [AppointmentStatus::CANCELADA->value])
            ->pluck('scheduled_time')
            ->map(fn ($t) => substr($t, 0, 5)) // normaliza 'HH:MM:SS' → 'HH:MM'
            ->all();

        if (! empty($bookedTimes)) {
            $slots = array_values(
                array_filter($slots, fn ($slot) => ! in_array($slot['time'], $bookedTimes))
            );
        }

        return $slots;
    }
}
