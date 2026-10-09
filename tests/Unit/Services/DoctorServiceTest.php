<?php

use App\Enums\UserRole;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\User;
use App\Services\DoctorService;

describe('DoctorService::create', function () {

    it('cria usuário médico e perfil em transação', function () {
        $specialty = Specialty::factory()->create();

        $doctor = app(DoctorService::class)->create([
            'name'          => 'Dr. Ana',
            'email'         => 'ana@example.com',
            'password'      => 'Senha@1234',
            'crm'           => '54321',
            'crm_uf'        => 'MG',
            'specialty_ids' => [$specialty->id],
        ]);

        expect($doctor->crm)->toBe('54321')
            ->and($doctor->crm_uf)->toBe('MG')
            ->and($doctor->user->email)->toBe('ana@example.com')
            ->and($doctor->user->role)->toBe(UserRole::MEDICO)
            ->and($doctor->specialties)->toHaveCount(1);

        $this->assertDatabaseHas('users', ['email' => 'ana@example.com']);
        $this->assertDatabaseHas('doctors', ['crm' => '54321']);
    });
});

describe('DoctorService::update', function () {

    it('atualiza bio e sincroniza especialidades', function () {
        $doctor    = Doctor::factory()->create();
        $specialty = Specialty::factory()->create();

        $updated = app(DoctorService::class)->update($doctor, [
            'bio'           => 'Especialista em X.',
            'specialty_ids' => [$specialty->id],
        ]);

        expect($updated->bio)->toBe('Especialista em X.')
            ->and($updated->specialties)->toHaveCount(1);
    });

    it('specialty_ids vazio remove todas as especialidades', function () {
        $specialty = Specialty::factory()->create();
        $doctor    = Doctor::factory()->create();
        $doctor->specialties()->attach($specialty);

        $updated = app(DoctorService::class)->update($doctor, ['specialty_ids' => []]);

        expect($updated->specialties)->toHaveCount(0);
    });
});

describe('DoctorService::toggleActive', function () {

    it('sincroniza is_active do usuário vinculado', function () {
        $doctor = Doctor::factory()->create(['is_active' => true]);

        $result = app(DoctorService::class)->toggleActive($doctor);

        expect($result->is_active)->toBeFalse()
            ->and($doctor->user->fresh()->is_active)->toBeFalse();
    });
});

describe('DoctorService::delete', function () {

    it('faz soft delete e desativa o usuário', function () {
        $doctor = Doctor::factory()->create();

        app(DoctorService::class)->delete($doctor);

        $this->assertSoftDeleted('doctors', ['id' => $doctor->id]);
        expect($doctor->user->fresh()->is_active)->toBeFalse();
    });
});
