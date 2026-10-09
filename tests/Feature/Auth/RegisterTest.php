<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;

describe('POST /api/v1/auth/register', function () {

    it('registra um novo paciente com sucesso', function () {
        Event::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'password' => 'senha@1234',
            'password_confirmation' => 'senha@1234',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['user' => ['id', 'name', 'email', 'role', 'is_active'], 'token'],
            ])
            ->assertJsonPath('data.user.role', 'paciente')
            ->assertJsonPath('data.user.email', 'joao@example.com');

        $this->assertDatabaseHas('users', ['email' => 'joao@example.com']);
        Event::assertDispatched(Registered::class);
    });

    it('rejeita e-mail já cadastrado', function () {
        User::factory()->create(['email' => 'existente@example.com']);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Outro',
            'email' => 'existente@example.com',
            'password' => 'senha@1234',
            'password_confirmation' => 'senha@1234',
        ])->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Este e-mail já está cadastrado.');
    });

    it('rejeita senha fraca', function () {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'João',
            'email' => 'joao@example.com',
            'password' => '123',
            'password_confirmation' => '123',
        ])->assertStatus(422)
            ->assertJsonStructure(['errors' => ['password']]);
    });

    it('rejeita confirmação de senha incorreta', function () {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'João',
            'email' => 'joao@example.com',
            'password' => 'senha@1234',
            'password_confirmation' => 'outrasenha',
        ])->assertStatus(422)
            ->assertJsonPath('errors.password.0', 'A confirmação de senha não confere.');
    });

    it('rejeita campos obrigatórios ausentes', function () {
        $this->postJson('/api/v1/auth/register', [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['name', 'email', 'password']]);
    });

    it('cria usuário sempre com perfil paciente', function () {
        Event::fake();

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Teste',
            'email' => 'teste@example.com',
            'password' => 'senha@1234',
            'password_confirmation' => 'senha@1234',
        ])->assertStatus(201);

        $user = User::where('email', 'teste@example.com')->first();
        expect($user->role)->toBe(UserRole::PACIENTE);
    });
});
