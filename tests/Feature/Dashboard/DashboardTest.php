<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;

describe('GET /api/v1/dashboard (admin)', function () {

    it('admin recebe totais e consultas do dia', function () {
        $admin = User::factory()->admin()->create();

        $doctors  = Doctor::factory()->count(3)->create();
        $inactive = Doctor::factory()->inactive()->count(1)->create();
        $patients = Patient::factory()->count(5)->create();

        $today = Carbon::today()->format('Y-m-d');
        Appointment::factory()->forDoctor($doctors[0])->forPatient($patients[0])->count(2)->create([
            'scheduled_date' => $today,
            'status'         => AppointmentStatus::AGENDADA,
        ]);
        Appointment::factory()->forDoctor($doctors[1])->forPatient($patients[1])->count(1)->create([
            'scheduled_date' => $today,
            'status'         => AppointmentStatus::CONCLUIDA,
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/dashboard')
            ->assertStatus(200);

        expect($response->json('data.totals.doctors'))->toBe(4)
            ->and($response->json('data.totals.doctors_active'))->toBe(3)
            ->and($response->json('data.totals.patients'))->toBe(5);

        expect($response->json('data.today_appointments'))->toHaveCount(3);
    });

    it('admin vê consultas por status', function () {
        $admin   = User::factory()->admin()->create();
        $doctor  = Doctor::factory()->create();
        $patient = Patient::factory()->create();
        Appointment::factory()->forDoctor($doctor)->forPatient($patient)->scheduled()->count(2)->create();
        Appointment::factory()->forDoctor($doctor)->forPatient($patient)->concluded()->count(3)->create();
        Appointment::factory()->forDoctor($doctor)->forPatient($patient)->cancelled()->count(1)->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/dashboard')
            ->assertStatus(200);

        $byStatus = $response->json('data.appointments_by_status');
        expect($byStatus['agendada'])->toBe(2)
            ->and($byStatus['concluida'])->toBe(3)
            ->and($byStatus['cancelada'])->toBe(1);
    });

    it('rejeita acesso não autenticado', function () {
        $this->getJson('/api/v1/dashboard')->assertStatus(401);
    });
});

describe('GET /api/v1/dashboard (médico)', function () {

    it('médico recebe agenda do dia e próximas consultas', function () {
        $user   = User::factory()->medico()->create();
        $doctor = Doctor::factory()->forUser($user)->create();

        $today    = Carbon::today()->format('Y-m-d');
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');

        Appointment::factory()->forDoctor($doctor)->count(2)->create([
            'scheduled_date' => $today,
            'status'         => AppointmentStatus::AGENDADA,
        ]);
        Appointment::factory()->forDoctor($doctor)->count(3)->create([
            'scheduled_date' => $tomorrow,
            'status'         => AppointmentStatus::AGENDADA,
        ]);
        // Outro médico — não deve aparecer
        Appointment::factory()->count(2)->create(['scheduled_date' => $today]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/dashboard')
            ->assertStatus(200);

        expect($response->json('data.today_appointments'))->toHaveCount(2);
        expect($response->json('data.upcoming_appointments'))->toHaveCount(3);
    });

    it('conta ausências da semana', function () {
        $user   = User::factory()->medico()->create();
        $doctor = Doctor::factory()->forUser($user)->create();

        $monday = Carbon::now()->startOfWeek()->format('Y-m-d');
        Appointment::factory()->forDoctor($doctor)->count(2)->create([
            'scheduled_date' => $monday,
            'status'         => AppointmentStatus::PACIENTE_AUSENTE,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/dashboard')
            ->assertStatus(200);

        expect($response->json('data.absent_this_week'))->toBe(2);
    });
});

describe('GET /api/v1/dashboard (paciente)', function () {

    it('paciente recebe próximas consultas e histórico', function () {
        $user    = User::factory()->paciente()->create();
        $patient = Patient::factory()->create(['user_id' => $user->id]);

        $tomorrow = Carbon::tomorrow()->format('Y-m-d');
        $yesterday = Carbon::yesterday()->format('Y-m-d');

        Appointment::factory()->forPatient($patient)->count(2)->create([
            'scheduled_date' => $tomorrow,
            'status'         => AppointmentStatus::AGENDADA,
        ]);
        Appointment::factory()->forPatient($patient)->count(3)->create([
            'scheduled_date' => $yesterday,
            'status'         => AppointmentStatus::CONCLUIDA,
        ]);
        // Outro paciente — não deve aparecer
        Appointment::factory()->count(2)->create(['scheduled_date' => $tomorrow]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/dashboard')
            ->assertStatus(200);

        expect($response->json('data.upcoming_appointments'))->toHaveCount(2);
        expect($response->json('data.recent_history'))->toHaveCount(3);
        expect($response->json('data.total_concluded'))->toBe(3);
    });
});
