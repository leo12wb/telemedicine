<?php

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

describe('POST /api/v1/doctors/{doctor}/photo', function () {
    beforeEach(fn () => Storage::fake('public'));

    it('admin faz upload da foto', function () {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();

        $response = $this->actingAs($admin)
            ->post("/api/v1/doctors/{$doctor->id}/photo", [
                'photo' => UploadedFile::fake()->create('foto.jpg', 100, 'image/jpeg'),
            ])
            ->assertStatus(200);

        expect($response->json('data.photo_url'))->not->toBeNull();
        Storage::disk('public')->assertExists("doctors/{$doctor->id}/photo.jpg");
    });

    it('próprio médico faz upload da foto', function () {
        $user = User::factory()->medico()->create();
        $doctor = Doctor::factory()->forUser($user)->create();

        $this->actingAs($user)
            ->post("/api/v1/doctors/{$doctor->id}/photo", [
                'photo' => UploadedFile::fake()->create('foto.jpg', 100, 'image/jpeg'),
            ])
            ->assertStatus(200);
    });

    it('outro médico não pode fazer upload', function () {
        $other = User::factory()->medico()->create();
        $doctor = Doctor::factory()->create();

        $this->actingAs($other)
            ->post("/api/v1/doctors/{$doctor->id}/photo", [
                'photo' => UploadedFile::fake()->create('foto.jpg', 100, 'image/jpeg'),
            ])
            ->assertStatus(403);
    });

    it('rejeita arquivo que não é imagem', function () {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();

        $this->actingAs($admin)
            ->post("/api/v1/doctors/{$doctor->id}/photo", [
                'photo' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            ])
            ->assertStatus(422);
    });

    it('rejeita arquivo maior que 2MB', function () {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();

        $this->actingAs($admin)
            ->post("/api/v1/doctors/{$doctor->id}/photo", [
                'photo' => UploadedFile::fake()->create('grande.jpg', 3000, 'image/jpeg'),
            ])
            ->assertStatus(422);
    });

    it('substituição de foto remove o arquivo anterior', function () {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();

        // Primeira foto (jpeg)
        $this->actingAs($admin)
            ->post("/api/v1/doctors/{$doctor->id}/photo", [
                'photo' => UploadedFile::fake()->create('a.jpg', 100, 'image/jpeg'),
            ]);

        $firstPath = $doctor->fresh()->photo_path;
        Storage::disk('public')->assertExists($firstPath);

        // Segunda foto (png) substitui a primeira — extensão diferente garante path distinto
        $this->actingAs($admin)
            ->post("/api/v1/doctors/{$doctor->id}/photo", [
                'photo' => UploadedFile::fake()->create('b.png', 100, 'image/png'),
            ]);

        Storage::disk('public')->assertMissing($firstPath);
    });

    it('não autenticado recebe 401', function () {
        $doctor = Doctor::factory()->create();

        $this->postJson("/api/v1/doctors/{$doctor->id}/photo", [
            'photo' => UploadedFile::fake()->create('foto.jpg', 100, 'image/jpeg'),
        ])->assertStatus(401);
    });
});

describe('DELETE /api/v1/doctors/{doctor}/photo', function () {
    beforeEach(fn () => Storage::fake('public'));

    it('admin remove foto', function () {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();

        Storage::disk('public')->put("doctors/{$doctor->id}/photo.jpg", 'fake');
        $doctor->update(['photo_path' => "doctors/{$doctor->id}/photo.jpg"]);

        $this->actingAs($admin)
            ->delete("/api/v1/doctors/{$doctor->id}/photo")
            ->assertStatus(200);

        expect($doctor->fresh()->photo_path)->toBeNull();
        Storage::disk('public')->assertMissing("doctors/{$doctor->id}/photo.jpg");
    });

    it('remoção sem foto existente retorna 200 sem erro', function () {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create(['photo_path' => null]);

        $this->actingAs($admin)
            ->delete("/api/v1/doctors/{$doctor->id}/photo")
            ->assertStatus(200);
    });
});
