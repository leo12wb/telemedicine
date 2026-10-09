<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentMessageController extends Controller
{
    private function authorizeParticipant(Appointment $appointment): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;
        $appointment->loadMissing('doctor.user', 'patient.user');
        if ($user->role->value === 'medico') {
            abort_if($appointment->doctor?->user_id !== $user->id, 403);
        } else {
            abort_if($appointment->patient?->user_id !== $user->id, 403);
        }
    }

    public function index(Appointment $appointment): JsonResponse
    {
        $this->authorizeParticipant($appointment);

        $messages = AppointmentMessage::with('user')
            ->where('appointment_id', $appointment->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn (AppointmentMessage $m) => [
                'id'         => $m->id,
                'user_id'    => $m->user_id,
                'user_name'  => $m->user->name ?? '',
                'body'       => $m->body,
                'created_at' => $m->created_at,
            ]);

        return response()->json(['data' => $messages]);
    }

    public function store(Appointment $appointment, Request $request): JsonResponse
    {
        $this->authorizeParticipant($appointment);

        $validated = $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        $message = AppointmentMessage::create([
            'appointment_id' => $appointment->id,
            'user_id'        => auth()->id(),
            'body'           => $validated['body'],
        ]);

        $message->load('user');

        return response()->json([
            'data' => [
                'id'         => $message->id,
                'user_id'    => $message->user_id,
                'user_name'  => $message->user->name ?? '',
                'body'       => $message->body,
                'created_at' => $message->created_at,
            ],
        ], 201);
    }
}
