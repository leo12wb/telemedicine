<?php

use App\Enums\UserRole;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\User;

describe('GET /api/v1/doctors', function () {

    it('admin lista todos os médicos incluindo inativos', function () {
        $admin = User::factory()->admin()->create();
        Doctor::factory()->count(3)->create();
        Doctor::factory()->inactive()->count(2)->create();

        $this->actingAs($admin)
            ->getJson('/api/v1/doctors')
            ->assertStatus(200)
            ->assertJsonCount(5, 'data');
    });

    it('paciente vê apenas médicos ativos', function () {
        $paciente = User::factory()->paciente()->create();
        Doctor::factory()->count(3)->create();
        Doctor::factory()->inactive()->count(2)->create();

        $this->actingAs($paciente)
            ->getJson('/api/v1/doctors')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    });

    it('filtra médicos por especialidade', function () {
        $admin = User::factory()->admin()->create();
        $specialty = Specialty::factory()->create();
        $doctor = Doctor::factory()->create();
        $doctor->specialties()->attach($specialty);
        Doctor::factory()->count(2)->create(); // sem especialidade

        $this->actingAs($admin)
            ->getJson("/api/v1/doctors?specialty_id={$specialty->id}")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    });

    it('rejeita acesso não autenticado', function () {
        $this->getJson('/api/v1/doctors')->assertStatus(401);
    });
});

describe('POST /api/v1/doctors', function () {

    it('admin cria médico com user e especialidades', function () {
        $admin = User::factory()->admin()->create();
        $specialty = Specialty::factory()->create();

        $this->actingAs($admin)
            ->postJson('/api/v1/doctors', [
                'name' => 'Dr. Pedro',
                'email' => 'pedro@example.com',
                'password' => 'Senha@1234',
                'crm' => '12345',
                'crm_uf' => 'SP',
                'specialty_ids' => [$specialty->id],
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.crm', '12345')
            ->assertJsonPath('data.crm_uf', 'SP')
            ->assertJsonPath('data.user.email', 'pedro@example.com')
            ->assertJsonCount(1, 'data.specialties');

        $this->assertDatabaseHas('users', ['email' => 'pedro@example.com', 'role' => UserRole::MEDICO->value]);
        $this->assertDatabaseHas('doctors', ['crm' => '12345', 'crm_uf' => 'SP']);
    });

    it('rejeita CRM duplicado na mesma UF', function () {
        $admin = User::factory()->admin()->create();
        Doctor::factory()->create(['crm' => '99999', 'crm_uf' => 'RJ']);

        $this->actingAs($admin)
            ->postJson('/api/v1/doctors', [
                'name' => 'Outro',
                'email' => 'outro@example.com',
                'password' => 'Senha@1234',
                'crm' => '99999',
                'crm_uf' => 'RJ',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['crm']);
    });

    it('permite mesmo CRM em UF diferente', function () {
        $admin = User::factory()->admin()->create();
        Doctor::factory()->create(['crm' => '99999', 'crm_uf' => 'SP']);

        $this->actingAs($admin)
            ->postJson('/api/v1/doctors', [
                'name' => 'Outro RJ',
                'email' => 'outrj@example.com',
                'password' => 'Senha@1234',
                'crm' => '99999',
                'crm_uf' => 'RJ',
            ])
            ->assertStatus(201);
    });

    it('paciente não pode cadastrar médicos', function () {
        $paciente = User::factory()->paciente()->create();

        $this->actingAs($paciente)
            ->postJson('/api/v1/doctors', [
                'name' => 'X', 'email' => 'x@x.com', 'password' => 'Senha@1234',
                'crm' => '11111', 'crm_uf' => 'SP',
            ])
            ->assertStatus(403);
    });
});

describe('GET /api/v1/doctors/{doctor}', function () {

    it('qualquer usuário autenticado visualiza médico com especialidades', function () {
        $paciente = User::factory()->paciente()->create();
        $specialty = Specialty::factory()->create(['name' => 'Ortopedia']);
        $doctor = Doctor::factory()->create();
        $doctor->specialties()->attach($specialty);

        $this->actingAs($paciente)
            ->getJson("/api/v1/doctors/{$doctor->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.specialties.0.name', 'Ortopedia');
    });
});

describe('PUT /api/v1/doctors/{doctor}', function () {

    it('admin atualiza dados e especialidades do médico', function () {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();
        $specialty = Specialty::factory()->create();

        $this->actingAs($admin)
            ->putJson("/api/v1/doctors/{$doctor->id}", [
                'bio' => 'Nova bio.',
                'specialty_ids' => [$specialty->id],
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.bio', 'Nova bio.')
            ->assertJsonCount(1, 'data.specialties');
    });

    it('médico atualiza o próprio bio e telefone', function () {
        $user = User::factory()->medico()->create();
        $doctor = Doctor::factory()->forUser($user)->create();

        $this->actingAs($user)
            ->putJson("/api/v1/doctors/{$doctor->id}", ['bio' => 'Minha bio.'])
            ->assertStatus(200)
            ->assertJsonPath('data.bio', 'Minha bio.');
    });

    it('médico não pode alterar o próprio CRM', function () {
        $user = User::factory()->medico()->create();
        $doctor = Doctor::factory()->forUser($user)->create();

        $this->actingAs($user)
            ->putJson("/api/v1/doctors/{$doctor->id}", ['crm' => '99999'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['crm']);
    });

    it('paciente não pode atualizar médico', function () {
        $paciente = User::factory()->paciente()->create();
        $doctor = Doctor::factory()->create();

        $this->actingAs($paciente)
            ->putJson("/api/v1/doctors/{$doctor->id}", ['bio' => 'x'])
            ->assertStatus(403);
    });
});

describe('PATCH /api/v1/doctors/{doctor}/toggle-active', function () {

    it('admin desativa médico e sincroniza status do usuário', function () {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create(['is_active' => true]);

        $this->actingAs($admin)
            ->patchJson("/api/v1/doctors/{$doctor->id}/toggle-active")
            ->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        expect($doctor->user->fresh()->is_active)->toBeFalse();
    });

    it('paciente não pode alterar status de médico', function () {
        $paciente = User::factory()->paciente()->create();
        $doctor = Doctor::factory()->create();

        $this->actingAs($paciente)
            ->patchJson("/api/v1/doctors/{$doctor->id}/toggle-active")
            ->assertStatus(403);
    });
});

describe('DELETE /api/v1/doctors/{doctor}', function () {

    it('admin remove médico (soft delete) e desativa o usuário', function () {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();

        $this->actingAs($admin)
            ->deleteJson("/api/v1/doctors/{$doctor->id}")
            ->assertStatus(200)
            ->assertJsonPath('message', 'Médico removido com sucesso.');

        $this->assertSoftDeleted('doctors', ['id' => $doctor->id]);
        expect($doctor->user->fresh()->is_active)->toBeFalse();
    });

    it('paciente não pode excluir médico', function () {
        $paciente = User::factory()->paciente()->create();
        $doctor = Doctor::factory()->create();

        $this->actingAs($paciente)
            ->deleteJson("/api/v1/doctors/{$doctor->id}")
            ->assertStatus(403);
    });
});
