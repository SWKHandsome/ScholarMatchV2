<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class IncomeCategoryFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->unique()->word(), 'minimum' => 0, 'maximum' => null, 'reference_year' => 2026, 'source_url' => 'https://example.test/income', 'is_active' => true];
    }
}
