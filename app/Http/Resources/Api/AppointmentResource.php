<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'doctor' => $this->whenLoaded('doctor', fn () => [
                'id'        => $this->doctor->id,
                'name'      => $this->doctor->user->name ?? null,
                'crm'       => $this->doctor->crm,
                'crm_uf'    => $this->doctor->crm_uf,
                'photo_url' => $this->doctor->photo_path ? \Illuminate\Support\Facades\Storage::url($this->doctor->photo_path) : null,
            ]),
            'patient' => $this->whenLoaded('patient', fn () => [
                'id' => $this->patient->id,
                'name' => $this->patient->user->name ?? null,
            ]),
            'scheduled_date' => $this->scheduled_date?->toDateString(),
            'scheduled_time' => $this->scheduled_time,
            'duration_minutes' => $this->duration_minutes,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'notes' => $this->notes,
            'cancellation_reason' => $this->cancellation_reason,
            'started_at' => $this->started_at,
            'ended_at' => $this->ended_at,
            'created_at' => $this->created_at,
        ];
    }
}
