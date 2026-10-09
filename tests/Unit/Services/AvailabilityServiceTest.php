<?php

use App\Models\Doctor;
use App\Models\DoctorBlock;
use App\Models\DoctorSchedule;
use App\Services\AvailabilityService;
use Carbon\Carbon;

describe('AvailabilityService::getAvailableSlots', function () {

    it('gera slots corretos para um horário de 2h com duração de 30min', function () {
        $doctor   = Doctor::factory()->create();
        $monday   = Carbon::now()->next(Carbon::MONDAY);

        DoctorSchedule::factory()->forDoctor($doctor)->create([
            'day_of_week'           => 1,
            'start_time'            => '08:00:00',
            'end_time'              => '10:00:00',
            'slot_duration_minutes' => 30,
            'is_active'             => true,
        ]);

        $slots = app(AvailabilityService::class)->getAvailableSlots($doctor, $monday->format('Y-m-d'));

        expect($slots)->toHaveCount(4);
        expect(array_column($slots, 'time'))->toBe(['08:00', '08:30', '09:00', '09:30']);
    });

    it('retorna vazio quando dia não tem horário cadastrado', function () {
        $doctor  = Doctor::factory()->create();
        $sunday  = Carbon::now()->next(Carbon::SUNDAY);

        // Cadastrar horário apenas para segunda (1), buscar no domingo (0)
        DoctorSchedule::factory()->forDoctor($doctor)->create([
            'day_of_week' => 1,
            'start_time'  => '08:00:00',
            'end_time'    => '12:00:00',
        ]);

        $slots = app(AvailabilityService::class)->getAvailableSlots($doctor, $sunday->format('Y-m-d'));

        expect($slots)->toBeEmpty();
    });

    it('bloqueio de dia inteiro retorna vazio', function () {
        $doctor = Doctor::factory()->create();
        $date   = Carbon::now()->next(Carbon::MONDAY);

        DoctorSchedule::factory()->forDoctor($doctor)->create([
            'day_of_week'           => 1,
            'start_time'            => '08:00:00',
            'end_time'              => '12:00:00',
            'slot_duration_minutes' => 30,
            'is_active'             => true,
        ]);

        DoctorBlock::factory()->forDoctor($doctor)->allDay()->create([
            'block_date' => $date->format('Y-m-d'),
        ]);

        $slots = app(AvailabilityService::class)->getAvailableSlots($doctor, $date->format('Y-m-d'));

        expect($slots)->toBeEmpty();
    });

    it('bloqueio parcial remove apenas os slots sobrepostos', function () {
        $doctor = Doctor::factory()->create();
        $date   = Carbon::now()->next(Carbon::MONDAY);

        DoctorSchedule::factory()->forDoctor($doctor)->create([
            'day_of_week'           => 1,
            'start_time'            => '08:00:00',
            'end_time'              => '12:00:00',
            'slot_duration_minutes' => 60,
            'is_active'             => true,
        ]);

        // Bloqueia 10:00–12:00 → remove slots 10:00 e 11:00
        DoctorBlock::factory()->forDoctor($doctor)->partial('10:00:00', '12:00:00')->create([
            'block_date' => $date->format('Y-m-d'),
        ]);

        $slots = app(AvailabilityService::class)->getAvailableSlots($doctor, $date->format('Y-m-d'));

        expect($slots)->toHaveCount(2);
        expect(array_column($slots, 'time'))->toBe(['08:00', '09:00']);
    });

    it('horário inativo não gera slots', function () {
        $doctor = Doctor::factory()->create();
        $date   = Carbon::now()->next(Carbon::MONDAY);

        DoctorSchedule::factory()->forDoctor($doctor)->inactive()->create([
            'day_of_week'           => 1,
            'start_time'            => '08:00:00',
            'end_time'              => '12:00:00',
            'slot_duration_minutes' => 30,
        ]);

        $slots = app(AvailabilityService::class)->getAvailableSlots($doctor, $date->format('Y-m-d'));

        expect($slots)->toBeEmpty();
    });

    it('múltiplos horários no mesmo dia são combinados', function () {
        $doctor = Doctor::factory()->create();
        $date   = Carbon::now()->next(Carbon::TUESDAY);

        // Manhã: 08:00–10:00 (2 slots de 60min)
        DoctorSchedule::factory()->forDoctor($doctor)->create([
            'day_of_week'           => 2,
            'start_time'            => '08:00:00',
            'end_time'              => '10:00:00',
            'slot_duration_minutes' => 60,
            'is_active'             => true,
        ]);

        // Tarde: 14:00–16:00 (2 slots de 60min)
        DoctorSchedule::factory()->forDoctor($doctor)->create([
            'day_of_week'           => 2,
            'start_time'            => '14:00:00',
            'end_time'              => '16:00:00',
            'slot_duration_minutes' => 60,
            'is_active'             => true,
        ]);

        $slots = app(AvailabilityService::class)->getAvailableSlots($doctor, $date->format('Y-m-d'));

        expect($slots)->toHaveCount(4);
    });
});

describe('AvailabilityService::createSchedule / deleteSchedule', function () {

    it('cria e persiste um horário', function () {
        $doctor = Doctor::factory()->create();

        $schedule = app(AvailabilityService::class)->createSchedule($doctor, [
            'day_of_week'           => 5,
            'start_time'            => '09:00',
            'end_time'              => '13:00',
            'slot_duration_minutes' => 45,
        ]);

        expect($schedule->day_of_week)->toBe(5)
            ->and($schedule->slot_duration_minutes)->toBe(45)
            ->and($schedule->is_active)->toBeTrue();
    });

    it('remove um horário', function () {
        $doctor   = Doctor::factory()->create();
        $schedule = DoctorSchedule::factory()->forDoctor($doctor)->create();

        app(AvailabilityService::class)->deleteSchedule($schedule);

        $this->assertDatabaseMissing('doctor_schedules', ['id' => $schedule->id]);
    });
});

describe('AvailabilityService::createBlock / deleteBlock', function () {

    it('cria bloqueio de dia inteiro', function () {
        $doctor = Doctor::factory()->create();

        $block = app(AvailabilityService::class)->createBlock($doctor, [
            'block_date' => '2030-07-04',
            'reason'     => 'Feriado',
        ]);

        expect($block->block_date->format('Y-m-d'))->toBe('2030-07-04')
            ->and($block->block_start)->toBeNull();
    });

    it('remove um bloqueio', function () {
        $doctor = Doctor::factory()->create();
        $block  = DoctorBlock::factory()->forDoctor($doctor)->create();

        app(AvailabilityService::class)->deleteBlock($block);

        $this->assertDatabaseMissing('doctor_blocks', ['id' => $block->id]);
    });
});
