<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorBlock extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'doctor_id',
        'block_date',
        'block_start',
        'block_end',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'block_date' => 'date',
        ];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
