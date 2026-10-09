<?php

use App\Models\User;

describe('POST /api/v1/auth/logout', function () {

    it('encerra a sessão revogando o token', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(200)
            ->assertJsonPath('message', 'Sessão encerrada com sucesso.');

        // Token revogado — não pode acessar rota protegida
        $this->assertDatabaseCount('personal_access_tokens', 0);
    });

    it('rejeita logout sem autenticação', function () {
        $this->postJson('/api/v1/auth/logout')
            ->assertStatus(401);
    });
});

describe('GET /api/v1/auth/me', function () {

    it('retorna dados do usuário autenticado', function () {
        $user = User::factory()->create(['name' => 'Maria Souza']);

        $this->actingAs($user)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Maria Souza')
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonStructure(['data' => ['id', 'name', 'email', 'role', 'is_active']]);
    });

    it('rejeita acesso sem autenticação', function () {
        $this->getJson('/api/v1/auth/me')
            ->assertStatus(401);
    });

    it('não expõe senha ou remember_token', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/auth/me')
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.remember_token');
    });
});
