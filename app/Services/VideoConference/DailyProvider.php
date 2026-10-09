<?php

namespace App\Services\VideoConference;

use App\Contracts\VideoConferenceProvider;
use App\Exceptions\VideoConferenceException;
use App\Models\Appointment;
use Illuminate\Support\Facades\Http;

class DailyProvider implements VideoConferenceProvider
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $domain,
    ) {}

    public function isEnabled(): bool
    {
        return true;
    }

    public function getMeetingUrl(Appointment $appointment): string
    {
        $roomName = 'consulta-'.substr(str_replace('-', '', $appointment->id), 0, 12);

        $this->ensureRoom($roomName);

        return "https://{$this->domain}.daily.co/{$roomName}";
    }

    private function ensureRoom(string $name): void
    {
        // Verifica se a sala já existe
        $check = Http::withToken($this->apiKey)
            ->get("https://api.daily.co/v1/rooms/{$name}");

        if ($check->successful()) {
            return;
        }

        if ($check->status() !== 404) {
            throw new VideoConferenceException(
                'Erro ao verificar sala no Daily.co: '.$check->status()
            );
        }

        // Cria a sala (expira em 7 dias)
        $create = Http::withToken($this->apiKey)
            ->post('https://api.daily.co/v1/rooms', [
                'name' => $name,
                'properties' => [
                    'exp' => time() + 60 * 60 * 24 * 7,
                    'enable_chat' => true,
                ],
            ]);

        // 200 = criada; 400 com "already exists" = outra requisição criou antes (ok)
        if (! $create->successful() && ! str_contains($create->body(), 'already exists')) {
            throw new VideoConferenceException(
                'Erro ao criar sala no Daily.co: '.$create->status()
            );
        }
    }
}
