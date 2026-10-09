<?php

use App\Models\SystemSetting;
use App\Models\User;

describe('GET /api/v1/settings', function () {
    it('admin lê configurações', function () {
        $admin = User::factory()->admin()->create();
        SystemSetting::setMany(['video_provider' => 'none']);

        $this->actingAs($admin)
            ->getJson('/api/v1/settings')
            ->assertStatus(200)
            ->assertJsonPath('data.video_provider', 'none');
    });

    it('não-admin recebe 403', function () {
        $user = User::factory()->medico()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/settings')
            ->assertStatus(403);
    });

    it('não autenticado recebe 401', function () {
        $this->getJson('/api/v1/settings')->assertStatus(401);
    });
});

describe('PUT /api/v1/settings', function () {
    it('admin configura provider jitsi', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->putJson('/api/v1/settings', [
                'video_provider' => 'jitsi',
                'jitsi_server_url' => 'https://meet.jit.si',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.video_provider', 'jitsi')
            ->assertJsonPath('data.jitsi_server_url', 'https://meet.jit.si');
    });

    it('admin configura provider daily', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->putJson('/api/v1/settings', [
                'video_provider' => 'daily',
                'daily_api_key' => 'sk_live_abc123',
                'daily_domain' => 'minhaempresa',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.video_provider', 'daily');
    });

    it('admin desativa videoconferência', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->putJson('/api/v1/settings', ['video_provider' => 'none'])
            ->assertStatus(200)
            ->assertJsonPath('data.video_provider', 'none');
    });

    it('jitsi sem server_url retorna 422', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->putJson('/api/v1/settings', ['video_provider' => 'jitsi'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['jitsi_server_url']);
    });

    it('daily sem api_key retorna 422', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->putJson('/api/v1/settings', [
                'video_provider' => 'daily',
                'daily_domain' => 'minhaempresa',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['daily_api_key']);
    });

    it('provider inválido retorna 422', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->putJson('/api/v1/settings', ['video_provider' => 'zoom'])
            ->assertStatus(422);
    });

    it('não-admin recebe 403', function () {
        $user = User::factory()->paciente()->create();

        $this->actingAs($user)
            ->putJson('/api/v1/settings', ['video_provider' => 'none'])
            ->assertStatus(403);
    });
});
