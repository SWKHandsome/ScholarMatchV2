<?php

namespace Database\Factories;

use App\Models\Scholarship;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScholarshipRequirementFactory extends Factory
{
    public function definition(): array
    {
        return ['scholarship_id' => Scholarship::factory()->withoutRequirements(), 'criteria' => array_fill_keys(array_keys(config('scholarmatch.criteria')), ['mode' => 'none'])];
    }
}
