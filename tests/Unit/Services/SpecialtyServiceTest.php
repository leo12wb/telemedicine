<?php

use App\Models\Specialty;
use App\Services\SpecialtyService;

describe('SpecialtyService::list', function () {

    it('retorna todas as especialidades para admin', function () {
        Specialty::factory()->count(3)->create();
        Specialty::factory()->inactive()->count(2)->create();

        $result = app(SpecialtyService::class)->list(onlyActive: false);

        expect($result->total())->toBe(5);
    });

    it('retorna apenas ativas quando solicitado', function () {
        Specialty::factory()->count(3)->create();
        Specialty::factory()->inactive()->count(2)->create();

        $result = app(SpecialtyService::class)->list(onlyActive: true);

        expect($result->total())->toBe(3);
    });
});

describe('SpecialtyService::listActive', function () {

    it('retorna collection sem paginação', function () {
        Specialty::factory()->count(4)->create();
        Specialty::factory()->inactive()->count(2)->create();

        $result = app(SpecialtyService::class)->listActive();

        expect($result)->toHaveCount(4);
    });
});

describe('SpecialtyService::create', function () {

    it('cria uma especialidade com os dados fornecidos', function () {
        $specialty = app(SpecialtyService::class)->create([
            'name' => 'Dermatologia',
            'description' => 'Pele e cabelo.',
        ]);

        expect($specialty->name)->toBe('Dermatologia')
            ->and($specialty->is_active)->toBeTrue();

        $this->assertDatabaseHas('specialties', ['name' => 'Dermatologia']);
    });
});

describe('SpecialtyService::update', function () {

    it('atualiza campos fornecidos', function () {
        $specialty = Specialty::factory()->create(['name' => 'Antigo']);

        $updated = app(SpecialtyService::class)->update($specialty, ['name' => 'Novo']);

        expect($updated->name)->toBe('Novo');
    });
});

describe('SpecialtyService::toggleActive', function () {

    it('alterna o status', function () {
        $specialty = Specialty::factory()->create(['is_active' => true]);

        $result = app(SpecialtyService::class)->toggleActive($specialty);
        expect($result->is_active)->toBeFalse();

        $result = app(SpecialtyService::class)->toggleActive($result);
        expect($result->is_active)->toBeTrue();
    });
});

describe('SpecialtyService::delete', function () {

    it('remove a especialidade do banco', function () {
        $specialty = Specialty::factory()->create();

        app(SpecialtyService::class)->delete($specialty);

        $this->assertDatabaseMissing('specialties', ['id' => $specialty->id]);
    });
});
