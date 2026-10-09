<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DoctorFactory extends Factory
{
    public function definition(): array
    {
        $ufs = ['SP', 'RJ', 'MG', 'RS', 'PR', 'BA', 'CE', 'GO', 'PE', 'SC'];

        return [
            'user_id'    => User::factory()->medico(),
            'crm'        => fake()->unique()->numerify('#####'),
            'crm_uf'     => fake()->randomElement($ufs),
            'phone'      => fake()->optional()->phoneNumber(),
            'bio'        => fake()->optional()->sentence(),
            'photo_path' => null,
            'is_active'  => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }
}
