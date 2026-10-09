<?php

use App\Enums\UserRole;
use App\Exceptions\AuthException;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;

describe('AuthService::login', function () {

    it('retorna user e token para credenciais válidas', function () {
        $user = User::factory()->create();

        $result = app(AuthService::class)->login($user->email, 'password');

        expect($result)->toHaveKeys(['user', 'token'])
            ->and($result['user']->id)->toBe($user->id)
            ->and($result['token'])->toBeString()->not->toBeEmpty();
    });

    it('lança AuthException para credenciais inválidas', function () {
        User::factory()->create(['email' => 'user@example.com']);

        expect(fn () => app(AuthService::class)->login('user@example.com', 'errada'))
            ->toThrow(AuthException::class, 'Credenciais inválidas.');
    });

    it('lança AuthException para conta inativa', function () {
        $user = User::factory()->inactive()->create();

        expect(fn () => app(AuthService::class)->login($user->email, 'password'))
            ->toThrow(AuthException::class, 'Conta desativada.');
    });
});

describe('AuthService::register', function () {

    it('cria usuário com perfil paciente', function () {
        Event::fake([Registered::class]);

        $result = app(AuthService::class)->register([
            'name'     => 'Novo Paciente',
            'email'    => 'novo@example.com',
            'password' => 'senha@1234',
        ]);

        expect($result['user']->role)->toBe(UserRole::PACIENTE)
            ->and($result['user']->email)->toBe('novo@example.com')
            ->and($result['token'])->toBeString();

        $this->assertDatabaseHas('users', ['email' => 'novo@example.com']);

        Event::assertDispatched(Registered::class);
    });
});
