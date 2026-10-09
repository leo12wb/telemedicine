<?php

use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;

describe('GET /api/v1/patients', function () {

    it('admin lista todos os pacientes', function () {
        $admin = User::factory()->admin()->create();
        Patient::factory()->count(4)->create();

        $this->actingAs($admin)
            ->getJson('/api/v1/patients')
            ->assertStatus(200)
            ->assertJsonCount(4, 'data');
    });

    it('médico pode listar pacientes', function () {
        $medico = User::factory()->medico()->create();
        Patient::factory()->count(2)->create();

        $this->actingAs($medico)
            ->getJson('/api/v1/patients')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    });

    it('paciente não pode listar todos os pacientes', function () {
        $paciente = User::factory()->paciente()->create();

        $this->actingAs($paciente)
            ->getJson('/api/v1/patients')
            ->assertStatus(403);
    });

    it('rejeita acesso não autenticado', function () {
        $this->getJson('/api/v1/patients')->assertStatus(401);
    });
});

describe('GET /api/v1/patients/profile', function () {

    it('paciente obtém o próprio perfil (cria se não existir)', function () {
        $user = User::factory()->paciente()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/patients/profile')
            ->assertStatus(200)
            ->assertJsonPath('data.user.id', $user->id);

        $this->assertDatabaseHas('patients', ['user_id' => $user->id]);
    });

    it('retorna perfil existente sem duplicar', function () {
        $user    = User::factory()->paciente()->create();
        $patient = Patient::factory()->forUser($user)->create();

        $this->actingAs($user)
            ->getJson('/api/v1/patients/profile')
            ->assertStatus(200)
            ->assertJsonPath('data.id', $patient->id);

        $this->assertDatabaseCount('patients', 1);
    });
});

describe('POST /api/v1/patients', function () {

    it('admin cria paciente com perfil clínico', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson('/api/v1/patients', [
                'name'            => 'Ana Paciente',
                'email'           => 'ana@example.com',
                'password'        => 'Senha@1234',
                'birth_date'      => '1990-05-15',
                'health_insurance'=> 'Unimed',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.health_insurance', 'Unimed')
            ->assertJsonPath('data.user.email', 'ana@example.com');

        $this->assertDatabaseHas('users', ['email' => 'ana@example.com', 'role' => UserRole::PACIENTE->value]);
        $this->assertDatabaseHas('patients', ['health_insurance' => 'Unimed']);
    });

    it('rejeita CPF duplicado', function () {
        $admin = User::factory()->admin()->create();
        Patient::factory()->withCpf()->create(['cpf' => '123.456.789-00']);

        $this->actingAs($admin)
            ->postJson('/api/v1/patients', [
                'name'     => 'Outro',
                'email'    => 'outro@example.com',
                'password' => 'Senha@1234',
                'cpf'      => '123.456.789-00',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['cpf']);
    });

    it('paciente não pode criar outros pacientes', function () {
        $paciente = User::factory()->paciente()->create();

        $this->actingAs($paciente)
            ->postJson('/api/v1/patients', [
                'name' => 'X', 'email' => 'x@x.com', 'password' => 'Senha@1234',
            ])
            ->assertStatus(403);
    });
});

describe('GET /api/v1/patients/{patient}', function () {

    it('admin visualiza qualquer paciente', function () {
        $admin   = User::factory()->admin()->create();
        $patient = Patient::factory()->create();

        $this->actingAs($admin)
            ->getJson("/api/v1/patients/{$patient->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $patient->id);
    });

    it('paciente visualiza o próprio perfil', function () {
        $user    = User::factory()->paciente()->create();
        $patient = Patient::factory()->forUser($user)->create();

        $this->actingAs($user)
            ->getJson("/api/v1/patients/{$patient->id}")
            ->assertStatus(200);
    });

    it('médico visualiza perfil de paciente', function () {
        $medico  = User::factory()->medico()->create();
        $patient = Patient::factory()->create();

        $this->actingAs($medico)
            ->getJson("/api/v1/patients/{$patient->id}")
            ->assertStatus(200);
    });

    it('paciente não visualiza perfil de outro paciente', function () {
        $paciente1 = User::factory()->paciente()->create();
        $patient2  = Patient::factory()->create();

        $this->actingAs($paciente1)
            ->getJson("/api/v1/patients/{$patient2->id}")
            ->assertStatus(403);
    });
});

describe('PUT /api/v1/patients/{patient}', function () {

    it('paciente atualiza o próprio perfil clínico', function () {
        $user    = User::factory()->paciente()->create();
        $patient = Patient::factory()->forUser($user)->create();

        $this->actingAs($user)
            ->putJson("/api/v1/patients/{$patient->id}", [
                'health_insurance' => 'Bradesco Saúde',
                'phone'            => '11999999999',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.health_insurance', 'Bradesco Saúde');
    });

    it('admin atualiza perfil de qualquer paciente', function () {
        $admin   = User::factory()->admin()->create();
        $patient = Patient::factory()->create();

        $this->actingAs($admin)
            ->putJson("/api/v1/patients/{$patient->id}", ['phone' => '11988887777'])
            ->assertStatus(200)
            ->assertJsonPath('data.phone', '11988887777');
    });

    it('paciente não atualiza perfil de outro paciente', function () {
        $paciente1 = User::factory()->paciente()->create();
        $patient2  = Patient::factory()->create();

        $this->actingAs($paciente1)
            ->putJson("/api/v1/patients/{$patient2->id}", ['phone' => '99999999'])
            ->assertStatus(403);
    });
});

describe('DELETE /api/v1/patients/{patient}', function () {

    it('admin remove paciente (soft delete) e desativa usuário', function () {
        $admin   = User::factory()->admin()->create();
        $patient = Patient::factory()->create();

        $this->actingAs($admin)
            ->deleteJson("/api/v1/patients/{$patient->id}")
            ->assertStatus(200)
            ->assertJsonPath('message', 'Paciente removido com sucesso.');

        $this->assertSoftDeleted('patients', ['id' => $patient->id]);
        expect($patient->user->fresh()->is_active)->toBeFalse();
    });

    it('paciente não pode se excluir', function () {
        $user    = User::factory()->paciente()->create();
        $patient = Patient::factory()->forUser($user)->create();

        $this->actingAs($user)
            ->deleteJson("/api/v1/patients/{$patient->id}")
            ->assertStatus(403);
    });
});
