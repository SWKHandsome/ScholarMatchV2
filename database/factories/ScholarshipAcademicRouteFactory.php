<?php

namespace Database\Factories;

use App\Models\Scholarship;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScholarshipAcademicRouteFactory extends Factory
{
    public function definition(): array
    {
        return ['scholarship_id' => Scholarship::factory(), 'qualification' => 'Diploma', 'completion_status' => 'completed', 'result_type' => 'cgpa', 'minimum' => 3.5, 'grading_scale' => 4];
    }
}
