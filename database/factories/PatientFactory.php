<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'                => User::factory()->paciente(),
            'cpf'                    => null,
            'birth_date'             => fake()->optional()->date('Y-m-d', '-18 years'),
            'phone'                  => fake()->optional()->phoneNumber(),
            'health_insurance'       => fake()->optional()->company(),
            'health_insurance_number'=> fake()->optional()->numerify('######'),
        ];
    }

    public function withCpf(): static
    {
        return $this->state(fn () => ['cpf' => fake()->unique()->numerify('###.###.###-##')]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }
}
