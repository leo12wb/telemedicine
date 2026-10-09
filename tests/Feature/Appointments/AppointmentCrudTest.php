<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\AppointmentBooked;
use App\Notifications\AppointmentCancelled;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

// ─────────────────────────────────────────────────────────────────────────────
// GET /api/v1/appointments
// ─────────────────────────────────────────────────────────────────────────────

describe('GET /api/v1/appointments', function () {

    it('admin vê todas as consultas', function () {
        $admin = User::factory()->admin()->create();
        Appointment::factory()->count(3)->create();

        $this->actingAs($admin)
            ->getJson('/api/v1/appointments')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    });

    it('médico vê apenas suas consultas', function () {
        $user = User::factory()->medico()->create();
        $doctor = Doctor::factory()->forUser($user)->create();
        Appointment::factory()->forDoctor($doctor)->count(2)->create();
        Appointment::factory()->count(3)->create(); // outro médico

        $this->actingAs($user)
            ->getJson('/api/v1/appointments')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    });

    it('paciente vê apenas suas consultas', function () {
        $user = User::factory()->paciente()->create();
        $patient = Patient::factory()->create(['user_id' => $user->id]);
        Appointment::factory()->forPatient($patient)->count(2)->create();
        Appointment::factory()->count(3)->create();

        $this->actingAs($user)
            ->getJson('/api/v1/appointments')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    });

    it('filtra por status', function () {
        $admin = User::factory()->admin()->create();
        Appointment::factory()->scheduled()->count(2)->create();
        Appointment::factory()->cancelled()->count(3)->create();

        $this->actingAs($admin)
            ->getJson('/api/v1/appointments?status=cancelada')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// POST /api/v1/appointments
// ─────────────────────────────────────────────────────────────────────────────

describe('POST /api/v1/appointments', function () {

    it('paciente agenda consulta com sucesso e recebe notificação', function () {
        Notification::fake();

        $userPaciente = User::factory()->paciente()->create();
        $patient = Patient::factory()->create(['user_id' => $userPaciente->id]);
        $doctor = Doctor::factory()->create();

        // Cria horário para segunda próxima
        $nextMonday = Carbon::now()->next(Carbon::MONDAY);
        DoctorSchedule::factory()->forDoctor($doctor)->create([
            'day_of_week' => 1,
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'slot_duration_minutes' => 60,
            'is_active' => true,
        ]);

        $this->actingAs($userPaciente)
            ->postJson('/api/v1/appointments', [
                'doctor_id' => $doctor->id,
                'scheduled_date' => $nextMonday->format('Y-m-d'),
                'scheduled_time' => '10:00',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.status', 'agendada');

        $this->assertDatabaseHas('appointments', [
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'status' => AppointmentStatus::AGENDADA->value,
        ]);

        Notification::assertSentTo($userPaciente, AppointmentBooked::class);
        Notification::assertSentTo($doctor->user, AppointmentBooked::class);
    });

    it('médico não pode agendar consulta', function () {
        $user = User::factory()->medico()->create();
        $doctor = Doctor::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/appointments', [
                'doctor_id' => $doctor->id,
                'scheduled_date' => Carbon::now()->addDays(3)->format('Y-m-d'),
                'scheduled_time' => '10:00',
            ])
            ->assertStatus(403);
    });

    it('rejeita agendamento em slot já ocupado (anti double-booking)', function () {
        Notification::fake();

        $user1 = User::factory()->paciente()->create();
        $patient1 = Patient::factory()->create(['user_id' => $user1->id]);
        $doctor = Doctor::factory()->create();
        $date = Carbon::now()->addDays(5)->format('Y-m-d');

        // Cria consulta existente no mesmo slot
        Appointment::factory()->forDoctor($doctor)->forPatient($patient1)->create([
            'scheduled_date' => $date,
            'scheduled_time' => '10:00:00',
            'status' => AppointmentStatus::AGENDADA,
        ]);

        $user2 = User::factory()->paciente()->create();
        Patient::factory()->create(['user_id' => $user2->id]);

        $this->actingAs($user2)
            ->postJson('/api/v1/appointments', [
                'doctor_id' => $doctor->id,
                'scheduled_date' => $date,
                'scheduled_time' => '10:00',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['scheduled_time']);
    });

    it('rejeita agendamento sem antecedência mínima de 2h', function () {
        $user = User::factory()->paciente()->create();
        Patient::factory()->create(['user_id' => $user->id]);
        $doctor = Doctor::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/appointments', [
                'doctor_id' => $doctor->id,
                'scheduled_date' => Carbon::now()->format('Y-m-d'),
                'scheduled_time' => Carbon::now()->addMinutes(30)->format('H:i'),
            ])
            ->assertStatus(422);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// GET /api/v1/appointments/{appointment}
// ─────────────────────────────────────────────────────────────────────────────

describe('GET /api/v1/appointments/{appointment}', function () {

    it('paciente visualiza sua própria consulta', function () {
        $user = User::factory()->paciente()->create();
        $patient = Patient::factory()->create(['user_id' => $user->id]);
        $appt = Appointment::factory()->forPatient($patient)->create();

        $this->actingAs($user)
            ->getJson("/api/v1/appointments/{$appt->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'agendada');
    });

    it('paciente não visualiza consulta de outro paciente', function () {
        $user = User::factory()->paciente()->create();
        Patient::factory()->create(['user_id' => $user->id]);
        $appt = Appointment::factory()->create(); // outro paciente

        $this->actingAs($user)
            ->getJson("/api/v1/appointments/{$appt->id}")
            ->assertStatus(403);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// PATCH /api/v1/appointments/{appointment}/cancel
// ─────────────────────────────────────────────────────────────────────────────

describe('PATCH /api/v1/appointments/{appointment}/cancel', function () {

    it('admin cancela qualquer consulta', function () {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $appt = Appointment::factory()->scheduled()->create();

        $this->actingAs($admin)
            ->patchJson("/api/v1/appointments/{$appt->id}/cancel", ['reason' => 'Erro de sistema'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelada');

        $this->assertDatabaseHas('appointments', [
            'id' => $appt->id,
            'status' => AppointmentStatus::CANCELADA->value,
        ]);

        Notification::assertSentTo($appt->patient->user, AppointmentCancelled::class);
    });

    it('médico cancela sua própria consulta', function () {
        Notification::fake();
        $user = User::factory()->medico()->create();
        $doctor = Doctor::factory()->forUser($user)->create();
        $appt = Appointment::factory()->forDoctor($doctor)->scheduled()->create([
            'scheduled_date' => Carbon::now()->addDays(5)->format('Y-m-d'),
        ]);

        $this->actingAs($user)
            ->patchJson("/api/v1/appointments/{$appt->id}/cancel")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelada');
    });

    it('médico não pode cancelar consulta de outro médico', function () {
        $user = User::factory()->medico()->create();
        $appt = Appointment::factory()->scheduled()->create(); // outro médico

        $this->actingAs($user)
            ->patchJson("/api/v1/appointments/{$appt->id}/cancel")
            ->assertStatus(403);
    });

    it('paciente não pode cancelar com menos de 24h de antecedência', function () {
        $user = User::factory()->paciente()->create();
        $patient = Patient::factory()->create(['user_id' => $user->id]);

        $appt = Appointment::factory()->forPatient($patient)->scheduled()->create([
            'scheduled_date' => Carbon::now()->format('Y-m-d'),
            'scheduled_time' => Carbon::now()->addHours(1)->format('H:i:s'),
        ]);

        $this->actingAs($user)
            ->patchJson("/api/v1/appointments/{$appt->id}/cancel")
            ->assertStatus(422);
    });

    it('não pode cancelar consulta já encerrada', function () {
        $admin = User::factory()->admin()->create();
        $appt = Appointment::factory()->concluded()->create();

        $this->actingAs($admin)
            ->patchJson("/api/v1/appointments/{$appt->id}/cancel")
            ->assertStatus(422);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// PATCH /start, /finish, /notes
// ─────────────────────────────────────────────────────────────────────────────

describe('PATCH /api/v1/appointments/{appointment}/start', function () {

    it('médico inicia sua consulta', function () {
        $user = User::factory()->medico()->create();
        $doctor = Doctor::factory()->forUser($user)->create();
        $appt = Appointment::factory()->forDoctor($doctor)->scheduled()->create();

        $this->actingAs($user)
            ->patchJson("/api/v1/appointments/{$appt->id}/start")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'em_andamento');

        expect($appt->fresh()->started_at)->not->toBeNull();
    });

    it('paciente não pode iniciar consulta', function () {
        $user = User::factory()->paciente()->create();
        $patient = Patient::factory()->create(['user_id' => $user->id]);
        $appt = Appointment::factory()->forPatient($patient)->scheduled()->create();

        $this->actingAs($user)
            ->patchJson("/api/v1/appointments/{$appt->id}/start")
            ->assertStatus(403);
    });

    it('não pode iniciar consulta que não está agendada', function () {
        $user = User::factory()->medico()->create();
        $doctor = Doctor::factory()->forUser($user)->create();
        $appt = Appointment::factory()->forDoctor($doctor)->inProgress()->create();

        $this->actingAs($user)
            ->patchJson("/api/v1/appointments/{$appt->id}/start")
            ->assertStatus(422);
    });
});

describe('PATCH /api/v1/appointments/{appointment}/finish', function () {

    it('médico encerra consulta como concluída', function () {
        $user = User::factory()->medico()->create();
        $doctor = Doctor::factory()->forUser($user)->create();
        $appt = Appointment::factory()->forDoctor($doctor)->inProgress()->create();

        $this->actingAs($user)
            ->patchJson("/api/v1/appointments/{$appt->id}/finish", [
                'outcome' => 'concluida',
                'notes' => 'Paciente em bom estado.',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'concluida')
            ->assertJsonPath('data.notes', 'Paciente em bom estado.');

        expect($appt->fresh()->ended_at)->not->toBeNull();
    });

    it('médico encerra como paciente ausente', function () {
        $user = User::factory()->medico()->create();
        $doctor = Doctor::factory()->forUser($user)->create();
        $appt = Appointment::factory()->forDoctor($doctor)->inProgress()->create();

        $this->actingAs($user)
            ->patchJson("/api/v1/appointments/{$appt->id}/finish", ['outcome' => 'paciente_ausente'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'paciente_ausente');
    });

    it('não pode encerrar consulta não iniciada', function () {
        $user = User::factory()->medico()->create();
        $doctor = Doctor::factory()->forUser($user)->create();
        $appt = Appointment::factory()->forDoctor($doctor)->scheduled()->create();

        $this->actingAs($user)
            ->patchJson("/api/v1/appointments/{$appt->id}/finish", ['outcome' => 'concluida'])
            ->assertStatus(422);
    });

    it('rejeita desfecho inválido', function () {
        $user = User::factory()->medico()->create();
        $doctor = Doctor::factory()->forUser($user)->create();
        $appt = Appointment::factory()->forDoctor($doctor)->inProgress()->create();

        $this->actingAs($user)
            ->patchJson("/api/v1/appointments/{$appt->id}/finish", ['outcome' => 'agendada'])
            ->assertStatus(422);
    });
});

describe('PATCH /api/v1/appointments/{appointment}/notes', function () {

    it('médico adiciona anotações clínicas', function () {
        $user = User::factory()->medico()->create();
        $doctor = Doctor::factory()->forUser($user)->create();
        $appt = Appointment::factory()->forDoctor($doctor)->inProgress()->create();

        $this->actingAs($user)
            ->patchJson("/api/v1/appointments/{$appt->id}/notes", ['notes' => 'PA: 12x8. Sem queixas.'])
            ->assertStatus(200)
            ->assertJsonPath('data.notes', 'PA: 12x8. Sem queixas.');
    });

    it('não pode editar anotações após encerramento', function () {
        $user = User::factory()->medico()->create();
        $doctor = Doctor::factory()->forUser($user)->create();
        $appt = Appointment::factory()->forDoctor($doctor)->concluded()->create();

        $this->actingAs($user)
            ->patchJson("/api/v1/appointments/{$appt->id}/notes", ['notes' => 'Tentativa pós-encerramento.'])
            ->assertStatus(422);
    });

    it('paciente não pode editar anotações', function () {
        $user = User::factory()->paciente()->create();
        $patient = Patient::factory()->create(['user_id' => $user->id]);
        $appt = Appointment::factory()->forPatient($patient)->inProgress()->create();

        $this->actingAs($user)
            ->patchJson("/api/v1/appointments/{$appt->id}/notes", ['notes' => 'X'])
            ->assertStatus(403);
    });
});
