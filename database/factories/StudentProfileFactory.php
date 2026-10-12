<?php

namespace Database\Factories;

use App\Models\StudyField;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(), 'citizenship' => 'Malaysian', 'residence_state' => 'Selangor',
            'current_education_level' => 'secondary', 'funding_level' => 'bachelor', 'study_field_id' => StudyField::factory(),
            'enrolment_status' => 'planning', 'institution_type' => 'public_university', 'study_location' => 'Malaysia',
            'household_income' => 0, 'academic_records_complete' => true,
        ];
    }
}
