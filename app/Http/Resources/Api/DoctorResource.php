<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class DoctorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'crm' => $this->crm,
            'crm_uf' => $this->crm_uf,
            'phone' => $this->phone,
            'bio' => $this->bio,
            'photo_url' => $this->photo_path ? Storage::url($this->photo_path) : null,
            'is_active' => $this->is_active,
            'user' => new UserResource($this->whenLoaded('user')),
            'specialties' => SpecialtyResource::collection($this->whenLoaded('specialties')),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
