<?php

namespace Database\Factories;

use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;

class DoctorScheduleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'doctor_id'             => Doctor::factory(),
            'day_of_week'           => $this->faker->numberBetween(1, 5), // seg-sex
            'start_time'            => '08:00:00',
            'end_time'              => '12:00:00',
            'slot_duration_minutes' => 30,
            'is_active'             => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function forDoctor(Doctor $doctor): static
    {
        return $this->state(['doctor_id' => $doctor->id]);
    }
}
