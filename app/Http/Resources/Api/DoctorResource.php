<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'photo_path' => $this->photo_path,
            'is_active' => $this->is_active,
            'user' => new UserResource($this->whenLoaded('user')),
            'specialties' => SpecialtyResource::collection($this->whenLoaded('specialties')),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
