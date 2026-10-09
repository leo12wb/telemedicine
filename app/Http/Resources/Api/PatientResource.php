<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cpf' => $this->cpf,
            'birth_date' => $this->birth_date?->toDateString(),
            'phone' => $this->phone,
            'health_insurance' => $this->health_insurance,
            'health_insurance_number' => $this->health_insurance_number,
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
