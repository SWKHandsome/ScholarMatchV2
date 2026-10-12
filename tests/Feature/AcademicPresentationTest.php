<?php

namespace Tests\Feature;

use App\Models\Scholarship;
use App\Models\ScholarshipAcademicRoute;
use App\Models\StudentAcademicRecord;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AcademicPresentationTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('routeDescriptions')]
    public function test_academic_requirements_are_readable_and_preserve_all_conditions(array $attributes, string $result, string $completion, string $summary): void
    {
        $scholarship = Scholarship::factory()->create();
        $scholarship->requirements->update(['criteria' => ['academic' => ['mode' => 'restricted']] + $scholarship->requirements->criteria]);
        ScholarshipAcademicRoute::factory()->create(['scholarship_id' => $scholarship->id] + $attributes);

        $this->actingAs(User::factory()->create())->get('/recommendations')
            ->assertOk()->assertSee('Academic pathways')->assertSee($result)->assertSee($completion)
            ->assertDontSee('Meet any one pathway')->assertDontSee('Number of As ≥')->assertDontSee('(scale ');
        $this->get('/scholarships/'.$scholarship->id)->assertOk()->assertSee($summary)->assertDontSee('(scale ');
    }

    public static function routeDescriptions(): array
    {
        return [
            'SPM count' => [['qualification' => 'SPM', 'result_type' => 'as_count', 'minimum' => 8, 'grading_scale' => 10], 'At least 8 As', 'Completed qualification', 'Completed SPM with at least 8 As'],
            'one A' => [['qualification' => 'SPM', 'result_type' => 'as_count', 'minimum' => 1, 'grading_scale' => 10], 'At least 1 A', 'Completed qualification', 'Completed SPM with at least 1 A'],
            'zero minimum' => [['qualification' => 'SPM', 'result_type' => 'as_count', 'minimum' => 0, 'grading_scale' => 10], 'At least 0 As', 'Completed qualification', 'Completed SPM with at least 0 As'],
            'CGPA out of four' => [[], 'CGPA of at least 3.50/4.00', 'Completed qualification', 'Completed Diploma with CGPA of at least 3.50/4.00'],
            'another CGPA scale' => [['minimum' => 4.2, 'grading_scale' => 5], 'CGPA of at least 4.20/5.00', 'Completed qualification', 'Completed Diploma with CGPA of at least 4.20/5.00'],
            'zero CGPA minimum' => [['minimum' => 0], 'CGPA of at least 0.00/4.00', 'Completed qualification', 'Completed Diploma with CGPA of at least 0.00/4.00'],
            'threshold precision' => [['minimum' => 3.5555], 'CGPA of at least 3.5555/4.00', 'Completed qualification', 'Completed Diploma with CGPA of at least 3.5555/4.00'],
            'in progress accepts completed too' => [['completion_status' => 'in_progress'], 'CGPA of at least 3.50/4.00', 'In progress or completed', 'Diploma (in progress or completed) with CGPA of at least 3.50/4.00'],
            'unknown completion' => [['completion_status' => 'unknown'], 'CGPA of at least 3.50/4.00', 'Completion status needs verification', 'Diploma (completion status needs verification) with CGPA of at least 3.50/4.00'],
            'missing minimum' => [['minimum' => null], 'CGPA minimum needs verification', 'Completed qualification', 'Completed Diploma with CGPA minimum needs verification'],
            'missing scale' => [['grading_scale' => null], 'CGPA of at least 3.50 (grading scale needs verification)', 'Completed qualification', 'Completed Diploma with CGPA of at least 3.50 (grading scale needs verification)'],
            'missing As' => [['qualification' => 'SPM', 'result_type' => 'as_count', 'minimum' => null], 'Required number of As needs verification', 'Completed qualification', 'Completed SPM with required number of As needs verification'],
            'unsupported result' => [['qualification' => 'Other', 'result_type' => 'other', 'minimum' => null, 'grading_scale' => null, 'manual_conditions' => 'Interview and subject review required.'], 'Results to verify with the provider', 'Completed qualification', 'Completed Other with results to verify with the provider; Interview and subject review required.'],
        ];
    }

    public function test_alternatives_are_separate_pathways_in_recommendations_dashboard_and_saved_cards(): void
    {
        $scholarship = Scholarship::factory()->create();
        $scholarship->requirements->update(['criteria' => ['academic' => ['mode' => 'restricted']] + $scholarship->requirements->criteria]);
        ScholarshipAcademicRoute::factory()->create(['scholarship_id' => $scholarship->id, 'qualification' => 'SPM', 'result_type' => 'as_count', 'minimum' => 8, 'grading_scale' => 10]);
        ScholarshipAcademicRoute::factory()->create(['scholarship_id' => $scholarship->id]);
        $profile = StudentProfile::factory()->create();
        StudentAcademicRecord::factory()->create(['student_profile_id' => $profile->id]);
        $this->actingAs($student = User::findOrFail($profile->user_id));
        $student->savedScholarships()->attach($scholarship);

        foreach (['/recommendations', '/dashboard', '/saved'] as $page) {
            $this->get($page)->assertOk()->assertSee('Meet any one pathway')
                ->assertSeeInOrder(['SPM', 'At least 8 As', 'Completed qualification', 'OR', 'Diploma', 'CGPA of at least 3.50/4.00'])
                ->assertDontSee('(scale ');
        }
    }

    public function test_unrestricted_and_manual_requirements_do_not_imply_a_numeric_pathway(): void
    {
        $unrestricted = Scholarship::factory()->create();
        $manual = Scholarship::factory()->create();
        $manual->requirements->update(['criteria' => ['academic' => ['mode' => 'manual', 'notes' => 'Verify subject grades with the provider.']] + $manual->requirements->criteria]);

        $this->actingAs(User::factory()->create())->get('/recommendations')->assertOk()
            ->assertSee('No academic restriction')->assertSee('Verify subject grades with the provider.')
            ->assertDontSee('Meet any one pathway')->assertDontSee('CGPA of at least');
    }

    public function test_manual_route_notes_are_visible_and_escaped(): void
    {
        $scholarship = Scholarship::factory()->create();
        $scholarship->requirements->update(['criteria' => ['academic' => ['mode' => 'restricted']] + $scholarship->requirements->criteria]);
        $note = '<script>alert("route")</script>';
        ScholarshipAcademicRoute::factory()->create(['scholarship_id' => $scholarship->id, 'manual_conditions' => $note]);

        $this->actingAs(User::factory()->create())->get('/recommendations')->assertOk()
            ->assertSee('Also verify')->assertSee($note)->assertDontSee($note, false);
        $this->get('/scholarships/'.$scholarship->id)->assertOk()->assertSee($note)->assertDontSee($note, false);
    }
}
