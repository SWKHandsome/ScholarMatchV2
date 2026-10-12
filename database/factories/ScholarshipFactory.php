<?php

namespace Database\Factories;

use App\Models\Scholarship;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScholarshipFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => 'Demo '.fake()->unique()->words(3, true).' Scholarship', 'provider' => 'Demo Foundation', 'description' => 'Fictional opportunity for testing.', 'benefits' => 'Full tuition and living allowance', 'deadline' => now()->addDays(30)->toDateString(), 'application_url' => 'https://example.test/apply', 'source_url' => 'https://example.test/source', 'status' => 'open', 'source_status' => 'reviewed', 'last_verified_at' => now(), 'is_fictional' => true];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Scholarship $scholarship) {
            if (! $scholarship->requirements()->exists()) {
                $scholarship->requirements()->create(['criteria' => array_fill_keys(array_keys(config('scholarmatch.criteria')), ['mode' => 'none'])]);
            }
        });
    }

    public function withoutRequirements(): static
    {
        return $this->newInstance(['afterCreating' => collect()]);
    }
}
