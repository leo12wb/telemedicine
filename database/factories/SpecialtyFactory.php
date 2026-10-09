<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SpecialtyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'        => fake()->unique()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'is_active'   => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
