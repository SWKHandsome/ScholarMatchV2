<?php

namespace Database\Factories;

use App\Models\StudentProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentAcademicRecordFactory extends Factory
{
    public function definition(): array
    {
        return ['student_profile_id' => StudentProfile::factory(), 'qualification' => 'Diploma', 'completion_status' => 'completed', 'result_type' => 'cgpa', 'result_value' => 3.8, 'grading_scale' => 4];
    }
}
