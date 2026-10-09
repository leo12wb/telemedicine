<?php

namespace Database\Factories;

use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;

class DoctorBlockFactory extends Factory
{
    public function definition(): array
    {
        return [
            'doctor_id'   => Doctor::factory(),
            'block_date'  => $this->faker->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'block_start' => null,
            'block_end'   => null,
            'reason'      => $this->faker->optional()->sentence(),
        ];
    }

    public function allDay(): static
    {
        return $this->state(['block_start' => null, 'block_end' => null]);
    }

    public function partial(string $start = '10:00:00', string $end = '12:00:00'): static
    {
        return $this->state(['block_start' => $start, 'block_end' => $end]);
    }

    public function forDoctor(Doctor $doctor): static
    {
        return $this->state(['doctor_id' => $doctor->id]);
    }
}
