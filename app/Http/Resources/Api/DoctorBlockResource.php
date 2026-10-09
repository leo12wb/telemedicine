<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorBlockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'doctor_id' => $this->doctor_id,
            'block_date' => $this->block_date?->toDateString(),
            'block_start' => $this->block_start,
            'block_end' => $this->block_end,
            'reason' => $this->reason,
            'is_all_day' => is_null($this->block_start),
            'created_at' => $this->created_at,
        ];
    }
}
