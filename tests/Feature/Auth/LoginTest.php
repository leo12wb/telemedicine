<?php

use App\Models\User;

describe('POST /api/v1/auth/login', function () {

    it('autentica usuário com credenciais válidas', function () {
        $user = User::factory()->create(['email' => 'user@example.com']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'password',
        ])->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['user' => ['id', 'name', 'email', 'role'], 'token'],
            ]);
    });

    it('rejeita senha incorreta', function () {
        User::factory()->create(['email' => 'user@example.com']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'senha_errada',
        ])->assertStatus(401)
            ->assertJsonPath('message', 'Credenciais inválidas.');
    });

    it('rejeita e-mail inexistente com mensagem genérica', function () {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'naoexiste@example.com',
            'password' => 'qualquer',
        ])->assertStatus(401)
            ->assertJsonPath('message', 'Credenciais inválidas.');
    });

    it('rejeita conta inativa', function () {
        User::factory()->inactive()->create(['email' => 'inativo@example.com']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'inativo@example.com',
            'password' => 'password',
        ])->assertStatus(403)
            ->assertJsonPath('message', 'Conta desativada. Entre em contato com o suporte.');
    });

    it('rejeita campos obrigatórios ausentes', function () {
        $this->postJson('/api/v1/auth/login', [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['email', 'password']]);
    });

    it('rejeita e-mail com formato inválido', function () {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'nao-eh-email',
            'password' => 'password',
        ])->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Informe um e-mail válido.');
    });
});
