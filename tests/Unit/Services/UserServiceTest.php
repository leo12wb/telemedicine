<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Support\Facades\Hash;

describe('UserService::list', function () {

    it('retorna todos os usuários paginados', function () {
        User::factory()->count(5)->create();

        $result = app(UserService::class)->list();

        expect($result->total())->toBe(5);
    });

    it('filtra por role', function () {
        User::factory()->admin()->count(2)->create();
        User::factory()->paciente()->count(3)->create();

        $result = app(UserService::class)->list(['role' => 'admin']);

        expect($result->total())->toBe(2);
    });

    it('filtra por is_active', function () {
        User::factory()->count(3)->create(['is_active' => true]);
        User::factory()->inactive()->count(2)->create();

        $result = app(UserService::class)->list(['is_active' => 'false']);

        expect($result->total())->toBe(2);
    });

    it('filtra por termo de busca', function () {
        User::factory()->create(['name' => 'Carlos Silva', 'email' => 'carlos@test.com']);
        User::factory()->create(['name' => 'Maria Souza', 'email' => 'maria@test.com']);

        $result = app(UserService::class)->list(['search' => 'Carlos']);

        expect($result->total())->toBe(1);
    });
});

describe('UserService::create', function () {

    it('cria usuário com os dados fornecidos', function () {
        $user = app(UserService::class)->create([
            'name'     => 'Dr. João',
            'email'    => 'joao@example.com',
            'password' => 'Senha@1234',
            'role'     => 'medico',
        ]);

        expect($user->role)->toBe(UserRole::MEDICO)
            ->and($user->name)->toBe('Dr. João')
            ->and($user->is_active)->toBeTrue();

        $this->assertDatabaseHas('users', ['email' => 'joao@example.com']);
    });
});

describe('UserService::update', function () {

    it('atualiza campos fornecidos', function () {
        $user = User::factory()->create(['name' => 'Antigo Nome']);

        $updated = app(UserService::class)->update($user, ['name' => 'Novo Nome']);

        expect($updated->name)->toBe('Novo Nome');
    });

    it('hasheia a senha ao atualizar', function () {
        $user = User::factory()->create();

        app(UserService::class)->update($user, ['password' => 'Nova@Senha1']);

        expect(Hash::check('Nova@Senha1', $user->fresh()->password))->toBeTrue();
    });
});

describe('UserService::toggleActive', function () {

    it('alterna o status ativo/inativo', function () {
        $user = User::factory()->create(['is_active' => true]);

        $result = app(UserService::class)->toggleActive($user);
        expect($result->is_active)->toBeFalse();

        $result = app(UserService::class)->toggleActive($result);
        expect($result->is_active)->toBeTrue();
    });
});

describe('UserService::delete', function () {

    it('realiza soft delete', function () {
        $user = User::factory()->create();

        app(UserService::class)->delete($user);

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    });
});
