<?php

namespace App\Http\Controllers\Api;

use App\Contracts\VideoConferenceProvider;
use App\Enums\AppointmentStatus;
use App\Exceptions\VideoConferenceException;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\VideoConferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeetingController extends Controller
{
    public function __construct(private readonly VideoConferenceService $conferenceService) {}

    /** GET /api/v1/appointments/{appointment}/meeting */
    public function show(Request $request, Appointment $appointment): JsonResponse
    {
        $user = $request->user();

        // Autorização: admin, médico da consulta ou paciente da consulta
        if (! $user->isAdmin()) {
            $appointment->loadMissing('doctor', 'patient');

            if ($user->role->value === 'medico') {
                abort_if($appointment->doctor->user_id !== $user->id, 403);
            } else {
                abort_if($appointment->patient->user_id !== $user->id, 403);
            }
        }

        // Apenas consultas agendadas ou em andamento têm sala disponível
        $validStatuses = [AppointmentStatus::AGENDADA, AppointmentStatus::EM_ANDAMENTO];
        if (! in_array($appointment->status, $validStatuses)) {
            return response()->json(['message' => 'Consulta não está disponível para videoconferência.'], 404);
        }

        try {
            $url = $this->conferenceService->getMeetingUrl($appointment);
        } catch (VideoConferenceException $e) {
            return response()->json(['message' => 'Serviço de videoconferência indisponível.'], 503);
        }

        if ($url === null) {
            return response()->json(['message' => 'Videoconferência não configurada.'], 404);
        }

        return response()->json(['data' => ['url' => $url]]);
    }
}
