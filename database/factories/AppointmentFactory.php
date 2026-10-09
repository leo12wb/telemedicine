<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'doctor_id'        => Doctor::factory(),
            'patient_id'       => Patient::factory(),
            'scheduled_date'   => $this->faker->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d'),
            'scheduled_time'   => $this->faker->randomElement(['08:00:00', '09:00:00', '10:00:00', '14:00:00', '15:00:00']),
            'duration_minutes' => 30,
            'status'           => AppointmentStatus::AGENDADA,
            'notes'            => null,
        ];
    }

    public function forDoctor(Doctor $doctor): static
    {
        return $this->state(['doctor_id' => $doctor->id]);
    }

    public function forPatient(Patient $patient): static
    {
        return $this->state(['patient_id' => $patient->id]);
    }

    public function scheduled(): static
    {
        return $this->state(['status' => AppointmentStatus::AGENDADA]);
    }

    public function inProgress(): static
    {
        return $this->state([
            'status'     => AppointmentStatus::EM_ANDAMENTO,
            'started_at' => now(),
        ]);
    }

    public function concluded(): static
    {
        return $this->state([
            'status'     => AppointmentStatus::CONCLUIDA,
            'started_at' => now()->subHour(),
            'ended_at'   => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => AppointmentStatus::CANCELADA]);
    }
}
