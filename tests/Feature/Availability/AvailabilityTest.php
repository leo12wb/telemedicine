<?php

use App\Models\Doctor;
use App\Models\DoctorBlock;
use App\Models\DoctorSchedule;
use App\Models\User;
use Carbon\Carbon;

// ─────────────────────────────────────────────────────────────────────────────
// Schedules
// ─────────────────────────────────────────────────────────────────────────────

describe('GET /api/v1/doctors/{doctor}/schedules', function () {

    it('qualquer usuário autenticado lista os horários de um médico', function () {
        $paciente = User::factory()->paciente()->create();
        $doctor   = Doctor::factory()->create();
        DoctorSchedule::factory()->forDoctor($doctor)->count(3)->create();

        $this->actingAs($paciente)
            ->getJson("/api/v1/doctors/{$doctor->id}/schedules")
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    });

    it('rejeita acesso não autenticado', function () {
        $doctor = Doctor::factory()->create();

        $this->getJson("/api/v1/doctors/{$doctor->id}/schedules")
            ->assertStatus(401);
    });
});

describe('POST /api/v1/doctors/{doctor}/schedules', function () {

    it('médico cria seu próprio horário', function () {
        $user   = User::factory()->medico()->create();
        $doctor = Doctor::factory()->forUser($user)->create();

        $this->actingAs($user)
            ->postJson("/api/v1/doctors/{$doctor->id}/schedules", [
                'day_of_week'           => 1,
                'start_time'            => '08:00',
                'end_time'              => '12:00',
                'slot_duration_minutes' => 30,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.day_of_week', 1)
            ->assertJsonPath('data.slot_duration_minutes', 30);

        $this->assertDatabaseHas('doctor_schedules', ['doctor_id' => $doctor->id, 'day_of_week' => 1]);
    });

    it('admin cria horário para qualquer médico', function () {
        $admin  = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();

        $this->actingAs($admin)
            ->postJson("/api/v1/doctors/{$doctor->id}/schedules", [
                'day_of_week'           => 3,
                'start_time'            => '14:00',
                'end_time'              => '18:00',
                'slot_duration_minutes' => 60,
            ])
            ->assertStatus(201);
    });

    it('paciente não pode criar horário de médico', function () {
        $paciente = User::factory()->paciente()->create();
        $doctor   = Doctor::factory()->create();

        $this->actingAs($paciente)
            ->postJson("/api/v1/doctors/{$doctor->id}/schedules", [
                'day_of_week'           => 1,
                'start_time'            => '08:00',
                'end_time'              => '12:00',
                'slot_duration_minutes' => 30,
            ])
            ->assertStatus(403);
    });

    it('médico não pode criar horário de outro médico', function () {
        $user     = User::factory()->medico()->create();
        $outroDoc = Doctor::factory()->create();

        $this->actingAs($user)
            ->postJson("/api/v1/doctors/{$outroDoc->id}/schedules", [
                'day_of_week'           => 1,
                'start_time'            => '08:00',
                'end_time'              => '12:00',
                'slot_duration_minutes' => 30,
            ])
            ->assertStatus(403);
    });

    it('rejeita end_time anterior ao start_time', function () {
        $user   = User::factory()->medico()->create();
        $doctor = Doctor::factory()->forUser($user)->create();

        $this->actingAs($user)
            ->postJson("/api/v1/doctors/{$doctor->id}/schedules", [
                'day_of_week'           => 1,
                'start_time'            => '12:00',
                'end_time'              => '08:00',
                'slot_duration_minutes' => 30,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['end_time']);
    });

    it('rejeita duração de slot inválida', function () {
        $user   = User::factory()->medico()->create();
        $doctor = Doctor::factory()->forUser($user)->create();

        $this->actingAs($user)
            ->postJson("/api/v1/doctors/{$doctor->id}/schedules", [
                'day_of_week'           => 1,
                'start_time'            => '08:00',
                'end_time'              => '12:00',
                'slot_duration_minutes' => 25,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['slot_duration_minutes']);
    });
});

describe('PUT /api/v1/doctors/{doctor}/schedules/{schedule}', function () {

    it('médico atualiza seu próprio horário', function () {
        $user     = User::factory()->medico()->create();
        $doctor   = Doctor::factory()->forUser($user)->create();
        $schedule = DoctorSchedule::factory()->forDoctor($doctor)->create(['is_active' => true]);

        $this->actingAs($user)
            ->putJson("/api/v1/doctors/{$doctor->id}/schedules/{$schedule->id}", [
                'is_active' => false,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.is_active', false);
    });

    it('retorna 404 para horário de outro médico', function () {
        $admin    = User::factory()->admin()->create();
        $doctor1  = Doctor::factory()->create();
        $doctor2  = Doctor::factory()->create();
        $schedule = DoctorSchedule::factory()->forDoctor($doctor2)->create();

        $this->actingAs($admin)
            ->putJson("/api/v1/doctors/{$doctor1->id}/schedules/{$schedule->id}", ['is_active' => false])
            ->assertStatus(404);
    });
});

describe('DELETE /api/v1/doctors/{doctor}/schedules/{schedule}', function () {

    it('médico remove seu próprio horário', function () {
        $user     = User::factory()->medico()->create();
        $doctor   = Doctor::factory()->forUser($user)->create();
        $schedule = DoctorSchedule::factory()->forDoctor($doctor)->create();

        $this->actingAs($user)
            ->deleteJson("/api/v1/doctors/{$doctor->id}/schedules/{$schedule->id}")
            ->assertStatus(200)
            ->assertJsonPath('message', 'Horário removido com sucesso.');

        $this->assertDatabaseMissing('doctor_schedules', ['id' => $schedule->id]);
    });

    it('paciente não pode excluir horário', function () {
        $paciente = User::factory()->paciente()->create();
        $doctor   = Doctor::factory()->create();
        $schedule = DoctorSchedule::factory()->forDoctor($doctor)->create();

        $this->actingAs($paciente)
            ->deleteJson("/api/v1/doctors/{$doctor->id}/schedules/{$schedule->id}")
            ->assertStatus(403);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Blocks
// ─────────────────────────────────────────────────────────────────────────────

describe('GET /api/v1/doctors/{doctor}/blocks', function () {

    it('lista bloqueios de um médico', function () {
        $admin  = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();
        DoctorBlock::factory()->forDoctor($doctor)->count(2)->create();

        $this->actingAs($admin)
            ->getJson("/api/v1/doctors/{$doctor->id}/blocks")
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    });

    it('filtra por período', function () {
        $admin  = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();
        DoctorBlock::factory()->forDoctor($doctor)->create(['block_date' => '2030-01-10']);
        DoctorBlock::factory()->forDoctor($doctor)->create(['block_date' => '2030-02-10']);

        $this->actingAs($admin)
            ->getJson("/api/v1/doctors/{$doctor->id}/blocks?from=2030-01-01&to=2030-01-31")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    });
});

describe('POST /api/v1/doctors/{doctor}/blocks', function () {

    it('médico cria bloqueio de dia inteiro', function () {
        $user   = User::factory()->medico()->create();
        $doctor = Doctor::factory()->forUser($user)->create();

        $this->actingAs($user)
            ->postJson("/api/v1/doctors/{$doctor->id}/blocks", [
                'block_date' => '2030-12-25',
                'reason'     => 'Natal',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.block_date', '2030-12-25')
            ->assertJsonPath('data.is_all_day', true);
    });

    it('médico cria bloqueio parcial', function () {
        $user   = User::factory()->medico()->create();
        $doctor = Doctor::factory()->forUser($user)->create();

        $this->actingAs($user)
            ->postJson("/api/v1/doctors/{$doctor->id}/blocks", [
                'block_date'  => '2030-11-01',
                'block_start' => '10:00',
                'block_end'   => '12:00',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.is_all_day', false);
    });

    it('rejeita block_end sem block_start', function () {
        $user   = User::factory()->medico()->create();
        $doctor = Doctor::factory()->forUser($user)->create();

        $this->actingAs($user)
            ->postJson("/api/v1/doctors/{$doctor->id}/blocks", [
                'block_date' => '2030-11-01',
                'block_end'  => '12:00',
            ])
            ->assertStatus(422);
    });

    it('paciente não pode criar bloqueio', function () {
        $paciente = User::factory()->paciente()->create();
        $doctor   = Doctor::factory()->create();

        $this->actingAs($paciente)
            ->postJson("/api/v1/doctors/{$doctor->id}/blocks", [
                'block_date' => '2030-12-25',
            ])
            ->assertStatus(403);
    });
});

describe('DELETE /api/v1/doctors/{doctor}/blocks/{block}', function () {

    it('médico remove seu bloqueio', function () {
        $user   = User::factory()->medico()->create();
        $doctor = Doctor::factory()->forUser($user)->create();
        $block  = DoctorBlock::factory()->forDoctor($doctor)->create();

        $this->actingAs($user)
            ->deleteJson("/api/v1/doctors/{$doctor->id}/blocks/{$block->id}")
            ->assertStatus(200)
            ->assertJsonPath('message', 'Bloqueio removido com sucesso.');

        $this->assertDatabaseMissing('doctor_blocks', ['id' => $block->id]);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Availability (slot calculation)
// ─────────────────────────────────────────────────────────────────────────────

describe('GET /api/v1/doctors/{doctor}/availability', function () {

    it('retorna slots disponíveis conforme o horário configurado', function () {
        $paciente = User::factory()->paciente()->create();
        $doctor   = Doctor::factory()->create();

        // Segunda-feira = 1
        $nextMonday = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');
        DoctorSchedule::factory()->forDoctor($doctor)->create([
            'day_of_week'           => 1,
            'start_time'            => '08:00:00',
            'end_time'              => '10:00:00',
            'slot_duration_minutes' => 60,
            'is_active'             => true,
        ]);

        $response = $this->actingAs($paciente)
            ->getJson("/api/v1/doctors/{$doctor->id}/availability?date={$nextMonday}")
            ->assertStatus(200);

        // 08:00 e 09:00 = 2 slots de 60 min no período 08:00–10:00
        expect($response->json('data'))->toHaveCount(2);
        expect($response->json('data.0.time'))->toBe('08:00');
        expect($response->json('data.1.time'))->toBe('09:00');
    });

    it('retorna array vazio quando não há horário no dia', function () {
        $paciente = User::factory()->paciente()->create();
        $doctor   = Doctor::factory()->create();

        // Nenhum horário cadastrado
        $this->actingAs($paciente)
            ->getJson("/api/v1/doctors/{$doctor->id}/availability?date=2030-01-06") // segunda
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');
    });

    it('retorna array vazio quando há bloqueio de dia inteiro', function () {
        $paciente = User::factory()->paciente()->create();
        $doctor   = Doctor::factory()->create();

        DoctorSchedule::factory()->forDoctor($doctor)->create([
            'day_of_week'           => 1,
            'start_time'            => '08:00:00',
            'end_time'              => '12:00:00',
            'slot_duration_minutes' => 30,
            'is_active'             => true,
        ]);

        // Bloqueio de dia inteiro na próxima segunda
        $nextMonday = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');
        DoctorBlock::factory()->forDoctor($doctor)->create([
            'block_date'  => $nextMonday,
            'block_start' => null,
            'block_end'   => null,
        ]);

        $this->actingAs($paciente)
            ->getJson("/api/v1/doctors/{$doctor->id}/availability?date={$nextMonday}")
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');
    });

    it('remove slots cobertos por bloqueio parcial', function () {
        $paciente = User::factory()->paciente()->create();
        $doctor   = Doctor::factory()->create();

        $nextMonday = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');
        DoctorSchedule::factory()->forDoctor($doctor)->create([
            'day_of_week'           => 1,
            'start_time'            => '08:00:00',
            'end_time'              => '12:00:00',
            'slot_duration_minutes' => 60,
            'is_active'             => true,
        ]);

        // Bloqueia 09:00–11:00 (remove slots 09:00 e 10:00)
        DoctorBlock::factory()->forDoctor($doctor)->create([
            'block_date'  => $nextMonday,
            'block_start' => '09:00:00',
            'block_end'   => '11:00:00',
        ]);

        $response = $this->actingAs($paciente)
            ->getJson("/api/v1/doctors/{$doctor->id}/availability?date={$nextMonday}")
            ->assertStatus(200);

        // Slots disponíveis: 08:00 e 11:00 (09:00 e 10:00 bloqueados)
        expect($response->json('data'))->toHaveCount(2);
        $times = array_column($response->json('data'), 'time');
        expect($times)->toContain('08:00');
        expect($times)->toContain('11:00');
        expect($times)->not->toContain('09:00');
        expect($times)->not->toContain('10:00');
    });

    it('não retorna slots de horário inativo', function () {
        $paciente = User::factory()->paciente()->create();
        $doctor   = Doctor::factory()->create();

        $nextMonday = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');
        DoctorSchedule::factory()->forDoctor($doctor)->create([
            'day_of_week'           => 1,
            'start_time'            => '08:00:00',
            'end_time'              => '12:00:00',
            'slot_duration_minutes' => 30,
            'is_active'             => false,
        ]);

        $this->actingAs($paciente)
            ->getJson("/api/v1/doctors/{$doctor->id}/availability?date={$nextMonday}")
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');
    });

    it('rejeita requisição sem parâmetro date', function () {
        $paciente = User::factory()->paciente()->create();
        $doctor   = Doctor::factory()->create();

        $this->actingAs($paciente)
            ->getJson("/api/v1/doctors/{$doctor->id}/availability")
            ->assertStatus(422);
    });
});
