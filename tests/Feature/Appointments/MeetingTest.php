<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\SystemSetting;
use App\Models\User;
use App\Enums\AppointmentStatus;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    SystemSetting::setMany(['video_provider' => 'none']);
});

describe('GET /api/v1/appointments/{appointment}/meeting', function () {
    it('retorna 404 quando provider é none', function () {
        $user = User::factory()->admin()->create();
        $appointment = Appointment::factory()->scheduled()->create();

        $this->actingAs($user)
            ->getJson("/api/v1/appointments/{$appointment->id}/meeting")
            ->assertStatus(404);
    });

    it('retorna URL jitsi para consulta agendada', function () {
        SystemSetting::setMany([
            'video_provider' => 'jitsi',
            'jitsi_server_url' => 'https://meet.jit.si',
        ]);

        $user = User::factory()->admin()->create();
        $appointment = Appointment::factory()->scheduled()->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/appointments/{$appointment->id}/meeting")
            ->assertStatus(200);

        expect($response->json('data.url'))->toStartWith('https://meet.jit.si/consulta-');
    });

    it('retorna URL jitsi para consulta em andamento', function () {
        SystemSetting::setMany([
            'video_provider' => 'jitsi',
            'jitsi_server_url' => 'https://jitsi.example.com',
        ]);

        $user = User::factory()->admin()->create();
        $appointment = Appointment::factory()->inProgress()->create();

        $this->actingAs($user)
            ->getJson("/api/v1/appointments/{$appointment->id}/meeting")
            ->assertStatus(200)
            ->assertJsonPath('data.url', fn ($url) => str_starts_with($url, 'https://jitsi.example.com/consulta-'));
    });

    it('retorna 404 para consulta concluída', function () {
        SystemSetting::setMany([
            'video_provider' => 'jitsi',
            'jitsi_server_url' => 'https://meet.jit.si',
        ]);

        $user = User::factory()->admin()->create();
        $appointment = Appointment::factory()->concluded()->create();

        $this->actingAs($user)
            ->getJson("/api/v1/appointments/{$appointment->id}/meeting")
            ->assertStatus(404);
    });

    it('retorna URL daily criando sala nova', function () {
        SystemSetting::setMany([
            'video_provider' => 'daily',
            'daily_api_key' => 'sk_live_test',
            'daily_domain' => 'minha-clinica',
        ]);

        Http::fake([
            'api.daily.co/*' => Http::sequence()
                ->push(null, 404)  // GET: sala não existe
                ->push(['url' => 'https://minha-clinica.daily.co/consulta-abc'], 200), // POST: criada
        ]);

        $user = User::factory()->admin()->create();
        $appointment = Appointment::factory()->scheduled()->create();

        $this->actingAs($user)
            ->getJson("/api/v1/appointments/{$appointment->id}/meeting")
            ->assertStatus(200)
            ->assertJsonPath('data.url', fn ($url) => str_contains($url, 'minha-clinica.daily.co'));
    });

    it('retorna URL daily quando sala já existe', function () {
        SystemSetting::setMany([
            'video_provider' => 'daily',
            'daily_api_key' => 'sk_live_test',
            'daily_domain' => 'minha-clinica',
        ]);

        Http::fake([
            'api.daily.co/v1/rooms/*' => Http::response(['url' => 'https://minha-clinica.daily.co/consulta-abc'], 200),
        ]);

        $user = User::factory()->admin()->create();
        $appointment = Appointment::factory()->scheduled()->create();

        $this->actingAs($user)
            ->getJson("/api/v1/appointments/{$appointment->id}/meeting")
            ->assertStatus(200);
    });

    it('retorna 503 quando Daily.co está fora', function () {
        SystemSetting::setMany([
            'video_provider' => 'daily',
            'daily_api_key' => 'sk_live_test',
            'daily_domain' => 'minha-clinica',
        ]);

        Http::fake([
            'api.daily.co/*' => Http::response([], 500),
        ]);

        $user = User::factory()->admin()->create();
        $appointment = Appointment::factory()->scheduled()->create();

        $this->actingAs($user)
            ->getJson("/api/v1/appointments/{$appointment->id}/meeting")
            ->assertStatus(503);
    });

    it('médico da consulta acessa a sala', function () {
        SystemSetting::setMany([
            'video_provider' => 'jitsi',
            'jitsi_server_url' => 'https://meet.jit.si',
        ]);

        $user = User::factory()->medico()->create();
        $doctor = Doctor::factory()->forUser($user)->create();
        $appointment = Appointment::factory()->forDoctor($doctor)->scheduled()->create();

        $this->actingAs($user)
            ->getJson("/api/v1/appointments/{$appointment->id}/meeting")
            ->assertStatus(200);
    });

    it('paciente da consulta acessa a sala', function () {
        SystemSetting::setMany([
            'video_provider' => 'jitsi',
            'jitsi_server_url' => 'https://meet.jit.si',
        ]);

        $user = User::factory()->paciente()->create();
        $patient = Patient::factory()->create(['user_id' => $user->id]);
        $appointment = Appointment::factory()->forPatient($patient)->scheduled()->create();

        $this->actingAs($user)
            ->getJson("/api/v1/appointments/{$appointment->id}/meeting")
            ->assertStatus(200);
    });

    it('outro médico não acessa a sala', function () {
        SystemSetting::setMany([
            'video_provider' => 'jitsi',
            'jitsi_server_url' => 'https://meet.jit.si',
        ]);

        $other = User::factory()->medico()->create();
        $appointment = Appointment::factory()->scheduled()->create();

        $this->actingAs($other)
            ->getJson("/api/v1/appointments/{$appointment->id}/meeting")
            ->assertStatus(403);
    });

    it('não autenticado recebe 401', function () {
        $appointment = Appointment::factory()->scheduled()->create();

        $this->getJson("/api/v1/appointments/{$appointment->id}/meeting")
            ->assertStatus(401);
    });
});
