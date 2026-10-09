<?php

use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;
use App\Services\PatientService;

describe('PatientService::create', function () {

    it('cria usuário paciente e perfil em transação', function () {
        $patient = app(PatientService::class)->create([
            'name'            => 'João Paciente',
            'email'           => 'joao@example.com',
            'password'        => 'Senha@1234',
            'birth_date'      => '1985-03-20',
            'health_insurance'=> 'Unimed',
        ]);

        expect($patient->user->email)->toBe('joao@example.com')
            ->and($patient->user->role)->toBe(UserRole::PACIENTE)
            ->and($patient->health_insurance)->toBe('Unimed')
            ->and($patient->birth_date->toDateString())->toBe('1985-03-20');

        $this->assertDatabaseHas('users', ['email' => 'joao@example.com']);
        $this->assertDatabaseHas('patients', ['health_insurance' => 'Unimed']);
    });
});

describe('PatientService::findOrCreateByUser', function () {

    it('cria perfil se não existir', function () {
        $user = User::factory()->paciente()->create();

        $patient = app(PatientService::class)->findOrCreateByUser($user);

        expect($patient->user_id)->toBe($user->id);
        $this->assertDatabaseHas('patients', ['user_id' => $user->id]);
    });

    it('retorna perfil existente sem duplicar', function () {
        $user    = User::factory()->paciente()->create();
        $existing = Patient::factory()->forUser($user)->create();

        $patient = app(PatientService::class)->findOrCreateByUser($user);

        expect($patient->id)->toBe($existing->id);
        $this->assertDatabaseCount('patients', 1);
    });
});

describe('PatientService::update', function () {

    it('atualiza campos fornecidos', function () {
        $patient = Patient::factory()->create();

        $updated = app(PatientService::class)->update($patient, [
            'phone'            => '11999998888',
            'health_insurance' => 'SulAmérica',
        ]);

        expect($updated->phone)->toBe('11999998888')
            ->and($updated->health_insurance)->toBe('SulAmérica');
    });
});

describe('PatientService::delete', function () {

    it('faz soft delete e desativa o usuário', function () {
        $patient = Patient::factory()->create();

        app(PatientService::class)->delete($patient);

        $this->assertSoftDeleted('patients', ['id' => $patient->id]);
        expect($patient->user->fresh()->is_active)->toBeFalse();
    });
});
