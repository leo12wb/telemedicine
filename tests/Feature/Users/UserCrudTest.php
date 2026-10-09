<?php

use App\Enums\UserRole;
use App\Models\User;

describe('GET /api/v1/users', function () {

    it('admin lista todos os usuários', function () {
        $admin = User::factory()->admin()->create();
        User::factory()->count(3)->create();

        $this->actingAs($admin)
            ->getJson('/api/v1/users')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta', 'links'])
            ->assertJsonCount(4, 'data'); // 3 + o próprio admin
    });

    it('admin filtra por role', function () {
        $admin = User::factory()->admin()->create();
        User::factory()->medico()->count(2)->create();
        User::factory()->paciente()->count(3)->create();

        $this->actingAs($admin)
            ->getJson('/api/v1/users?role=medico')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    });

    it('admin filtra por status ativo', function () {
        $admin = User::factory()->admin()->create();
        User::factory()->count(2)->create();
        User::factory()->inactive()->count(1)->create();

        $this->actingAs($admin)
            ->getJson('/api/v1/users?is_active=true')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data'); // admin + 2 ativos
    });

    it('paciente não pode listar todos os usuários', function () {
        $paciente = User::factory()->paciente()->create();

        $this->actingAs($paciente)
            ->getJson('/api/v1/users')
            ->assertStatus(403);
    });

    it('rejeita acesso não autenticado', function () {
        $this->getJson('/api/v1/users')
            ->assertStatus(401);
    });
});

describe('POST /api/v1/users', function () {

    it('admin cria novo usuário médico', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson('/api/v1/users', [
                'name'     => 'Dr. Carlos',
                'email'    => 'carlos@example.com',
                'password' => 'Senha@1234',
                'role'     => 'medico',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.role', 'medico')
            ->assertJsonPath('data.email', 'carlos@example.com');

        $this->assertDatabaseHas('users', ['email' => 'carlos@example.com']);
    });

    it('paciente não pode criar usuários', function () {
        $paciente = User::factory()->paciente()->create();

        $this->actingAs($paciente)
            ->postJson('/api/v1/users', [
                'name'     => 'Novo',
                'email'    => 'novo@example.com',
                'password' => 'Senha@1234',
                'role'     => 'paciente',
            ])
            ->assertStatus(403);
    });

    it('rejeita e-mail duplicado', function () {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['email' => 'existente@example.com']);

        $this->actingAs($admin)
            ->postJson('/api/v1/users', [
                'name'     => 'Outro',
                'email'    => 'existente@example.com',
                'password' => 'Senha@1234',
                'role'     => 'paciente',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('rejeita role inválida', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson('/api/v1/users', [
                'name'     => 'Teste',
                'email'    => 'teste@example.com',
                'password' => 'Senha@1234',
                'role'     => 'superadmin',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    });
});

describe('GET /api/v1/users/{user}', function () {

    it('admin visualiza qualquer usuário', function () {
        $admin = User::factory()->admin()->create();
        $user  = User::factory()->paciente()->create(['name' => 'João Paciente']);

        $this->actingAs($admin)
            ->getJson("/api/v1/users/{$user->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'João Paciente');
    });

    it('usuário visualiza o próprio perfil', function () {
        $user = User::factory()->paciente()->create(['name' => 'Próprio']);

        $this->actingAs($user)
            ->getJson("/api/v1/users/{$user->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Próprio');
    });

    it('paciente não pode visualizar outro usuário', function () {
        $paciente = User::factory()->paciente()->create();
        $outro    = User::factory()->paciente()->create();

        $this->actingAs($paciente)
            ->getJson("/api/v1/users/{$outro->id}")
            ->assertStatus(403);
    });
});

describe('PUT /api/v1/users/{user}', function () {

    it('admin atualiza dados de qualquer usuário', function () {
        $admin = User::factory()->admin()->create();
        $user  = User::factory()->paciente()->create();

        $this->actingAs($admin)
            ->putJson("/api/v1/users/{$user->id}", ['name' => 'Nome Atualizado'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Nome Atualizado');
    });

    it('usuário atualiza o próprio nome e senha', function () {
        $user = User::factory()->paciente()->create();

        $this->actingAs($user)
            ->putJson("/api/v1/users/{$user->id}", ['name' => 'Novo Nome'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Novo Nome');
    });

    it('usuário comum não pode alterar o próprio role', function () {
        $user = User::factory()->paciente()->create();

        $this->actingAs($user)
            ->putJson("/api/v1/users/{$user->id}", ['role' => 'admin'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    });

    it('paciente não pode atualizar outro usuário', function () {
        $paciente = User::factory()->paciente()->create();
        $outro    = User::factory()->paciente()->create();

        $this->actingAs($paciente)
            ->putJson("/api/v1/users/{$outro->id}", ['name' => 'Invasão'])
            ->assertStatus(403);
    });
});

describe('PATCH /api/v1/users/{user}/toggle-active', function () {

    it('admin desativa e reativa um usuário', function () {
        $admin = User::factory()->admin()->create();
        $user  = User::factory()->create(['is_active' => true]);

        $this->actingAs($admin)
            ->patchJson("/api/v1/users/{$user->id}/toggle-active")
            ->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        $this->actingAs($admin)
            ->patchJson("/api/v1/users/{$user->id}/toggle-active")
            ->assertStatus(200)
            ->assertJsonPath('data.is_active', true);
    });

    it('paciente não pode alterar o status de outro usuário', function () {
        $paciente = User::factory()->paciente()->create();
        $outro    = User::factory()->create();

        $this->actingAs($paciente)
            ->patchJson("/api/v1/users/{$outro->id}/toggle-active")
            ->assertStatus(403);
    });
});

describe('DELETE /api/v1/users/{user}', function () {

    it('admin remove um usuário (soft delete)', function () {
        $admin = User::factory()->admin()->create();
        $user  = User::factory()->create();

        $this->actingAs($admin)
            ->deleteJson("/api/v1/users/{$user->id}")
            ->assertStatus(200)
            ->assertJsonPath('message', 'Usuário removido com sucesso.');

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    });

    it('admin não pode se auto-excluir', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->deleteJson("/api/v1/users/{$admin->id}")
            ->assertStatus(403);
    });

    it('paciente não pode excluir usuários', function () {
        $paciente = User::factory()->paciente()->create();
        $outro    = User::factory()->create();

        $this->actingAs($paciente)
            ->deleteJson("/api/v1/users/{$outro->id}")
            ->assertStatus(403);
    });
});
