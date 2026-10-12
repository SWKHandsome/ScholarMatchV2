<?php

namespace Tests\Feature;

use App\Models\ScholarshipRequirement;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_tables_and_unique_profile_ownership(): void
    {
        foreach (['student_profiles', 'student_academic_records', 'scholarships', 'scholarship_requirements', 'scholarship_academic_routes', 'study_fields', 'income_categories', 'scholarship_study_fields', 'scholarship_income_categories', 'saved_scholarships'] as $table) {
            $this->assertTrue(Schema::hasTable($table), $table);
        }
        $user = User::factory()->create();
        DB::table('student_profiles')->insert(['user_id' => $user->id]);
        $this->expectException(QueryException::class);
        DB::table('student_profiles')->insert(['user_id' => $user->id]);
    }

    public function test_academic_records_cannot_reference_a_missing_profile(): void
    {
        $this->expectException(QueryException::class);
        DB::table('student_academic_records')->insert(['student_profile_id' => 999, 'qualification' => 'SPM', 'completion_status' => 'completed', 'result_type' => 'as_count', 'result_value' => 8, 'grading_scale' => 10]);
    }

    public function test_requirement_factory_creates_exactly_one_parent_configuration(): void
    {
        $requirement = ScholarshipRequirement::factory()->create();
        $this->assertDatabaseCount('scholarships', 1);
        $this->assertDatabaseCount('scholarship_requirements', 1);
        $this->assertDatabaseHas('scholarship_requirements', ['id' => $requirement->id]);
    }
}
