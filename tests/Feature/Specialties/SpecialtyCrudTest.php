<?php

use App\Models\Specialty;
use App\Models\User;

describe('GET /api/v1/specialties', function () {

    it('admin vê todas as especialidades incluindo inativas', function () {
        $admin = User::factory()->admin()->create();
        Specialty::factory()->count(3)->create();
        Specialty::factory()->inactive()->count(2)->create();

        $this->actingAs($admin)
            ->getJson('/api/v1/specialties')
            ->assertStatus(200)
            ->assertJsonCount(5, 'data');
    });

    it('paciente vê apenas especialidades ativas', function () {
        $paciente = User::factory()->paciente()->create();
        Specialty::factory()->count(3)->create();
        Specialty::factory()->inactive()->count(2)->create();

        $this->actingAs($paciente)
            ->getJson('/api/v1/specialties')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    });

    it('rejeita acesso não autenticado', function () {
        $this->getJson('/api/v1/specialties')
            ->assertStatus(401);
    });
});

describe('GET /api/v1/specialties/active', function () {

    it('retorna apenas especialidades ativas sem paginação', function () {
        $user = User::factory()->create();
        Specialty::factory()->count(4)->create();
        Specialty::factory()->inactive()->count(2)->create();

        $this->actingAs($user)
            ->getJson('/api/v1/specialties/active')
            ->assertStatus(200)
            ->assertJsonCount(4, 'data');
    });
});

describe('POST /api/v1/specialties', function () {

    it('admin cria nova especialidade', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson('/api/v1/specialties', [
                'name' => 'Cardiologia',
                'description' => 'Especialidade do coração.',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'Cardiologia')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('specialties', ['name' => 'Cardiologia']);
    });

    it('rejeita nome duplicado', function () {
        $admin = User::factory()->admin()->create();
        Specialty::factory()->create(['name' => 'Cardiologia']);

        $this->actingAs($admin)
            ->postJson('/api/v1/specialties', ['name' => 'Cardiologia'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    it('paciente não pode criar especialidades', function () {
        $paciente = User::factory()->paciente()->create();

        $this->actingAs($paciente)
            ->postJson('/api/v1/specialties', ['name' => 'Neurologia'])
            ->assertStatus(403);
    });
});

describe('GET /api/v1/specialties/{specialty}', function () {

    it('qualquer usuário autenticado visualiza uma especialidade', function () {
        $user = User::factory()->paciente()->create();
        $specialty = Specialty::factory()->create(['name' => 'Ortopedia']);

        $this->actingAs($user)
            ->getJson("/api/v1/specialties/{$specialty->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Ortopedia');
    });
});

describe('PUT /api/v1/specialties/{specialty}', function () {

    it('admin atualiza nome e descrição', function () {
        $admin = User::factory()->admin()->create();
        $specialty = Specialty::factory()->create(['name' => 'Antigo']);

        $this->actingAs($admin)
            ->putJson("/api/v1/specialties/{$specialty->id}", ['name' => 'Novo Nome'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Novo Nome');
    });

    it('paciente não pode atualizar especialidades', function () {
        $paciente = User::factory()->paciente()->create();
        $specialty = Specialty::factory()->create();

        $this->actingAs($paciente)
            ->putJson("/api/v1/specialties/{$specialty->id}", ['name' => 'Invasão'])
            ->assertStatus(403);
    });
});

describe('PATCH /api/v1/specialties/{specialty}/toggle-active', function () {

    it('admin desativa e reativa uma especialidade', function () {
        $admin = User::factory()->admin()->create();
        $specialty = Specialty::factory()->create(['is_active' => true]);

        $this->actingAs($admin)
            ->patchJson("/api/v1/specialties/{$specialty->id}/toggle-active")
            ->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        $this->actingAs($admin)
            ->patchJson("/api/v1/specialties/{$specialty->id}/toggle-active")
            ->assertStatus(200)
            ->assertJsonPath('data.is_active', true);
    });

    it('paciente não pode alterar status de especialidade', function () {
        $paciente = User::factory()->paciente()->create();
        $specialty = Specialty::factory()->create();

        $this->actingAs($paciente)
            ->patchJson("/api/v1/specialties/{$specialty->id}/toggle-active")
            ->assertStatus(403);
    });
});

describe('DELETE /api/v1/specialties/{specialty}', function () {

    it('admin remove uma especialidade', function () {
        $admin = User::factory()->admin()->create();
        $specialty = Specialty::factory()->create();

        $this->actingAs($admin)
            ->deleteJson("/api/v1/specialties/{$specialty->id}")
            ->assertStatus(200)
            ->assertJsonPath('message', 'Especialidade removida com sucesso.');

        $this->assertDatabaseMissing('specialties', ['id' => $specialty->id]);
    });

    it('paciente não pode excluir especialidades', function () {
        $paciente = User::factory()->paciente()->create();
        $specialty = Specialty::factory()->create();

        $this->actingAs($paciente)
            ->deleteJson("/api/v1/specialties/{$specialty->id}")
            ->assertStatus(403);
    });
});
