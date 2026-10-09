<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AppointmentAttachmentController extends Controller
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

        $attachments = AppointmentAttachment::with('user')
            ->where('appointment_id', $appointment->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn (AppointmentAttachment $a) => [
                'id'            => $a->id,
                'user_name'     => $a->user->name ?? '',
                'original_name' => $a->original_name,
                'mime_type'     => $a->mime_type,
                'size'          => $a->size,
                'created_at'    => $a->created_at,
            ]);

        return response()->json(['data' => $attachments]);
    }

    public function store(Appointment $appointment, Request $request): JsonResponse
    {
        $this->authorizeParticipant($appointment);

        $request->validate([
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
        ]);

        $file = $request->file('file');
        $uuid = (string) Str::uuid();
        $ext  = $file->getClientOriginalExtension();
        $path = "appointments/{$appointment->id}/{$uuid}.{$ext}";

        Storage::disk('public')->putFileAs(
            "appointments/{$appointment->id}",
            $file,
            "{$uuid}.{$ext}"
        );

        $attachment = AppointmentAttachment::create([
            'appointment_id' => $appointment->id,
            'user_id'        => auth()->id(),
            'original_name'  => $file->getClientOriginalName(),
            'file_path'      => $path,
            'mime_type'      => $file->getMimeType(),
            'size'           => $file->getSize(),
        ]);

        $attachment->load('user');

        return response()->json([
            'data' => [
                'id'            => $attachment->id,
                'user_name'     => $attachment->user->name ?? '',
                'original_name' => $attachment->original_name,
                'mime_type'     => $attachment->mime_type,
                'size'          => $attachment->size,
                'created_at'    => $attachment->created_at,
            ],
        ], 201);
    }

    public function download(Appointment $appointment, AppointmentAttachment $attachment): StreamedResponse
    {
        $this->authorizeParticipant($appointment);

        abort_if($attachment->appointment_id !== $appointment->id, 404);

        return Storage::disk('public')->download($attachment->file_path, $attachment->original_name);
    }

    public function destroy(Appointment $appointment, AppointmentAttachment $attachment): JsonResponse
    {
        $user = auth()->user();
        $this->authorizeParticipant($appointment);

        abort_if($attachment->appointment_id !== $appointment->id, 404);

        if (! $user->isAdmin() && $attachment->user_id !== $user->id) {
            abort(403);
        }

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return response()->json(null, 204);
    }
}
