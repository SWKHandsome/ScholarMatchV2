<?php

namespace Tests\Feature;

use App\Models\IncomeCategory;
use App\Models\Scholarship;
use App\Models\StudentAcademicRecord;
use App\Models\StudentProfile;
use App\Models\StudyField;
use App\Models\User;
use App\Services\ScholarshipEligibilityService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScholarshipAdministrationTest extends TestCase
{
    use RefreshDatabase;

    private function data(): array
    {
        return ['name' => 'Demo STEM Award', 'provider' => 'Demo Provider', 'description' => 'A fictional award.', 'benefits' => 'Tuition fees', 'deadline' => '2027-01-31', 'application_url' => 'https://example.test/apply', 'source_url' => 'https://example.test/source', 'source_status' => 'reviewed', 'status' => 'open', 'is_fictional' => true, 'criteria' => array_fill_keys(array_keys(config('scholarmatch.criteria')), ['mode' => 'none'])];
    }

    /**
     * @return array{name: string, provider: string, description: string, deadline: string, source_status: string, status: string, is_fictional: bool, criteria: array<string, array{mode: string}>, scholarship_url: string, income_requirement: string}
     */
    private function simplifiedData(): array
    {
        $data = $this->data();
        unset($data['benefits'], $data['application_url'], $data['source_url']);
        $data['scholarship_url'] = 'https://example.test/scholarship';
        $data['income_requirement'] = 'none';

        return $data;
    }

    public function test_empty_automatic_eligibility_sections_save_as_no_restriction(): void
    {
        $data = $this->simplifiedData();
        unset($data['criteria'], $data['income_requirement']);
        $data['automatic_criteria'] = true;
        $this->actingAs(User::factory()->create(['role' => 'admin']))->post('/admin/scholarships', $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(array_fill_keys(array_keys(config('scholarmatch.criteria')), ['mode' => 'none']), Scholarship::firstOrFail()->requirements->criteria);
    }

    public function test_selected_values_and_routes_automatically_restrict_only_the_completed_sections(): void
    {
        $category = IncomeCategory::factory()->create(['maximum' => 4999.99]);
        $field = StudyField::factory()->create();
        $data = $this->simplifiedData();
        unset($data['income_requirement']);
        $data['automatic_criteria'] = true;
        $data['criteria']['citizenship']['values'] = ['Malaysian'];
        $data['criteria']['funding_level']['values'] = ['bachelor'];
        $data['criteria']['study_field']['values'] = [$field->id];
        $data['criteria']['income']['category_ids'] = [$category->id];
        $data['academic_routes'] = [['qualification' => 'Diploma', 'completion_status' => 'completed', 'result_type' => 'cgpa', 'minimum' => 0]];
        $data['manual_requirements'] = 'Must show leadership experience.';
        $this->actingAs(User::factory()->create(['role' => 'admin']))->post('/admin/scholarships', $data)->assertSessionHasNoErrors()->assertRedirect();

        $scholarship = Scholarship::withEvaluationData()->firstOrFail();
        foreach (['academic', 'citizenship', 'funding_level', 'study_field', 'income'] as $criterion) {
            $this->assertSame('restricted', $scholarship->requirements->criteria[$criterion]['mode']);
        }
        $this->assertSame(['mode' => 'none'], $scholarship->requirements->criteria['residence_state']);
        $this->assertSame(['mode' => 'manual', 'notes' => $data['manual_requirements']], $scholarship->requirements->criteria['additional']);
        $this->assertSame([$category->id], $scholarship->incomeCategories->modelKeys());
        $this->assertSame([$field->id], $scholarship->studyFields->modelKeys());
        $this->assertSame('0.0000', $scholarship->academicRoutes->first()->minimum);
    }

    public function test_clearing_automatic_requirements_removes_restrictions_and_manual_notes(): void
    {
        $scholarship = Scholarship::factory()->create();
        $scholarship->requirements->update(['criteria' => ['citizenship' => ['mode' => 'restricted', 'values' => ['Malaysian']], 'additional' => ['mode' => 'manual', 'notes' => 'Pass an interview.']] + $scholarship->requirements->criteria]);
        $scholarship->studyFields()->attach(StudyField::factory()->create());
        $scholarship->incomeCategories()->attach(IncomeCategory::factory()->create());
        $scholarship->academicRoutes()->create(['qualification' => 'Diploma', 'completion_status' => 'completed', 'result_type' => 'cgpa', 'minimum' => 3, 'grading_scale' => 4]);
        $data = $this->simplifiedData();
        unset($data['criteria'], $data['income_requirement']);
        $data['automatic_criteria'] = true;
        $data['manual_requirements'] = '';
        $this->actingAs(User::factory()->create(['role' => 'admin']))->put('/admin/scholarships/'.$scholarship->id, $data)->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame(array_fill_keys(array_keys(config('scholarmatch.criteria')), ['mode' => 'none']), $scholarship->requirements->fresh()->criteria);
        $this->assertDatabaseCount('scholarship_study_fields', 0);
        $this->assertDatabaseCount('scholarship_income_categories', 0);
        $this->assertDatabaseCount('scholarship_academic_routes', 0);
    }

    public function test_existing_manual_notes_are_prefilled_and_can_be_saved_in_the_separate_section(): void
    {
        $scholarship = Scholarship::factory()->create();
        $scholarship->requirements->update(['criteria' => ['citizenship' => ['mode' => 'manual', 'notes' => 'Check dual citizenship.'], 'income' => ['mode' => 'manual', 'notes' => 'Verify variable income.']] + $scholarship->requirements->criteria]);
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/admin/scholarships/'.$scholarship->id.'/edit')->assertOk()->assertSee('Check dual citizenship.')->assertSee('Verify variable income.')->assertDontSee('Criterion mode');
        $data = $this->simplifiedData();
        unset($data['criteria'], $data['income_requirement']);
        $data['automatic_criteria'] = true;
        $data['manual_requirements'] = $scholarship->manualVerificationNotes();
        $this->put('/admin/scholarships/'.$scholarship->id, $data)->assertSessionHasNoErrors()->assertRedirect();
        $criteria = $scholarship->requirements->fresh()->criteria;
        $this->assertSame('none', $criteria['income']['mode']);
        $this->assertSame('none', $criteria['citizenship']['mode']);
        $this->assertSame($data['manual_requirements'], $criteria['additional']['notes']);
    }

    public function test_unsupported_academic_results_use_the_special_requirements_box(): void
    {
        $data = $this->simplifiedData();
        unset($data['criteria'], $data['income_requirement']);
        $data['automatic_criteria'] = true;
        $data['academic_routes'] = [['qualification' => 'Other', 'completion_status' => 'completed', 'result_type' => 'other']];
        $this->actingAs(User::factory()->create(['role' => 'admin']))->post('/admin/scholarships', $data)->assertSessionHasErrors('manual_requirements');
        $this->assertDatabaseCount('scholarships', 0);
        $data['manual_requirements'] = 'Provider must review the subject grades.';
        $this->post('/admin/scholarships', $data)->assertSessionHasNoErrors()->assertRedirect();
    }

    public function test_academic_routes_save_without_scale_inputs_and_as_counts_still_match(): void
    {
        $data = $this->simplifiedData();
        $data['criteria']['academic'] = ['mode' => 'restricted'];
        $data['academic_routes'] = [
            ['qualification' => 'SPM', 'completion_status' => 'completed', 'result_type' => 'as_count', 'minimum' => 8],
            ['qualification' => 'Diploma', 'completion_status' => 'completed', 'result_type' => 'cgpa', 'minimum' => 3.5],
        ];
        $this->actingAs(User::factory()->create(['role' => 'admin']))->post('/admin/scholarships', $data)->assertSessionHasNoErrors()->assertRedirect();

        $scholarship = Scholarship::withEvaluationData()->firstOrFail();
        $this->assertSame('4.0000', $scholarship->academicRoutes->firstWhere('result_type', 'cgpa')->grading_scale);
        $this->assertNull($scholarship->academicRoutes->firstWhere('result_type', 'as_count')->grading_scale);
        $this->get('/admin/scholarships/'.$scholarship->id.'/edit')->assertOk()->assertSee('Minimum CGPA (out of 4.00)')->assertDontSee('Grading scale maximum')->assertDontSee('[grading_scale]', false);

        $profile = StudentProfile::factory()->create(['academic_records_complete' => true]);
        StudentAcademicRecord::factory()->create(['student_profile_id' => $profile->id, 'qualification' => 'SPM', 'result_type' => 'as_count', 'result_value' => 8, 'grading_scale' => null]);
        $result = app(ScholarshipEligibilityService::class)->evaluate($profile->load('academicRecords'), $scholarship, collect());
        $this->assertSame('Eligible', $result->status->value);
        $this->assertStringContainsString('8 As', $result->academicRoutes[0]['student']);
        $this->assertStringNotContainsString('unknown scale', $result->academicRoutes[0]['student']);
    }

    public function test_minimum_cgpa_cannot_exceed_four_even_when_a_different_scale_is_submitted(): void
    {
        $data = $this->simplifiedData();
        $data['criteria']['academic'] = ['mode' => 'restricted'];
        $data['academic_routes'] = [['qualification' => 'Diploma', 'completion_status' => 'completed', 'result_type' => 'cgpa', 'minimum' => 4.01, 'grading_scale' => 5]];
        $this->actingAs(User::factory()->create(['role' => 'admin']))->post('/admin/scholarships', $data)->assertSessionHasErrors('academic_routes.0.minimum');
        $this->assertDatabaseCount('scholarships', 0);

        $data['academic_routes'][0]['minimum'] = 4;
        $this->post('/admin/scholarships', $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('4.0000', Scholarship::firstOrFail()->academicRoutes->firstOrFail()->grading_scale);
    }

    public function test_simplified_form_shows_one_description_and_one_link_without_advanced_income_inputs(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/admin/scholarships/create')
            ->assertOk()
            ->assertSee('Official scholarship link *')
            ->assertSee('Scholarship description *')
            ->assertDontSee('name="benefits"', false)
            ->assertSee('name="manual_requirements"', false)
            ->assertDontSee('Criterion mode')
            ->assertDontSee('name="income_requirement"', false)
            ->assertDontSee('name="source_url"', false)
            ->assertDontSee('name="application_url"', false)
            ->assertDontSee('name="closing_at"', false)
            ->assertDontSee('name="criteria[income][mode]"', false)
            ->assertDontSee('name="criteria[income][minimum]"', false)
            ->assertDontSee('name="criteria[income][maximum]"', false)
            ->assertDontSee('name="criteria[income][reference_year]"', false);
    }

    public function test_simplified_form_saves_description_without_benefits_or_separate_links(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post('/admin/scholarships', $this->simplifiedData())
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $scholarship = Scholarship::firstOrFail();
        $this->assertSame('A fictional award.', $scholarship->description);
        $this->assertSame('', $scholarship->benefits);
        $this->assertSame('https://example.test/scholarship', $scholarship->source_url);
        $this->assertSame($scholarship->source_url, $scholarship->application_url);
        $this->assertNull($scholarship->closing_at);
        $this->assertSame(['mode' => 'none'], $scholarship->requirements->criteria['income']);
    }

    public function test_description_is_required_and_existing_benefits_are_preserved_when_editing(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $data = $this->simplifiedData();
        unset($data['description']);
        $this->post('/admin/scholarships', $data)->assertSessionHasErrors('description');
        $this->assertDatabaseCount('scholarships', 0);

        $scholarship = Scholarship::factory()->create(['description' => 'Supports undergraduate study.', 'benefits' => 'Full tuition and monthly allowance.']);
        $this->get('/admin/scholarships/'.$scholarship->id.'/edit')->assertOk()->assertSee('Supports undergraduate study.')->assertSee('Full tuition and monthly allowance.');
        $data['automatic_criteria'] = true;
        $data['description'] = $scholarship->fullDescription();
        $this->put('/admin/scholarships/'.$scholarship->id, $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame($data['description'], $scholarship->fresh()->description);
        $this->assertSame('', $scholarship->fresh()->benefits);
        $this->assertSame($data['description'], $scholarship->fresh()->fullDescription());
    }

    public function test_residence_restrictions_accept_malaysian_states_and_territories_and_reject_foreign_places(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $data = $this->simplifiedData();
        $data['automatic_criteria'] = true;
        $data['criteria']['residence_state'] = ['values' => ['Penang', 'Kuala Lumpur', 'Labuan', 'Putrajaya']];
        $this->post('/admin/scholarships', $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame($data['criteria']['residence_state']['values'], Scholarship::firstOrFail()->requirements->criteria['residence_state']['values']);
        $data['criteria']['residence_state']['values'] = ['Singapore'];
        $this->post('/admin/scholarships', $data)->assertSessionHasErrors('criteria.residence_state.values.0');
        $this->assertDatabaseCount('scholarships', 1);
    }

    public function test_simplified_form_rejects_invalid_links_and_an_unanswered_income_requirement(): void
    {
        $data = $this->simplifiedData();
        $data['scholarship_url'] = 'javascript:alert(1)';
        $data['income_requirement'] = '';

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post('/admin/scholarships', $data)
            ->assertSessionHasErrors(['scholarship_url', 'income_requirement']);
        $this->assertDatabaseCount('scholarships', 0);
    }

    public function test_editing_basic_information_preserves_a_saved_application_link_and_closing_time(): void
    {
        $scholarship = Scholarship::factory()->create(['deadline' => '2027-01-31', 'closing_at' => '2027-01-31 09:00:00']);
        $data = $this->simplifiedData();
        $data['scholarship_url'] = $scholarship->source_url;

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->put('/admin/scholarships/'.$scholarship->id, $data)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $updated = $scholarship->fresh();
        $this->assertSame($scholarship->application_url, $updated->application_url);
        $this->assertSame($scholarship->source_url, $updated->source_url);
        $this->assertSame($scholarship->closing_at, $updated->closing_at);
    }

    public function test_changing_the_official_link_and_deadline_updates_both_links_and_clears_the_old_closing_time(): void
    {
        $scholarship = Scholarship::factory()->create(['deadline' => '2027-01-30', 'closing_at' => '2027-01-30 09:00:00']);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->put('/admin/scholarships/'.$scholarship->id, $this->simplifiedData())
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $updated = $scholarship->fresh();
        $this->assertSame('https://example.test/scholarship', $updated->source_url);
        $this->assertSame($updated->source_url, $updated->application_url);
        $this->assertNull($updated->closing_at);
    }

    public function test_admin_can_create_alternative_routes_and_source_review_is_audited(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $data = $this->data();
        $data['criteria']['academic'] = ['mode' => 'restricted'];
        $data['academic_routes'] = [['qualification' => 'SPM', 'completion_status' => 'completed', 'result_type' => 'as_count', 'minimum' => 8, 'grading_scale' => 10], ['qualification' => 'Diploma', 'completion_status' => 'completed', 'result_type' => 'cgpa', 'minimum' => 3.5, 'grading_scale' => 4]];
        $this->actingAs($admin)->post('/admin/scholarships', $data)->assertRedirect();
        $s = Scholarship::firstOrFail();
        $this->assertCount(2, $s->academicRoutes);
        $this->assertSame($admin->id, $s->verified_by);
        $this->assertNotNull($s->last_verified_at);
        foreach (['/admin/scholarships', '/admin/scholarships/create', '/admin/scholarships/'.$s->id.'/edit', '/admin/scholarships/'.$s->id] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_unreviewed_blanks_cannot_publish_and_drafts_are_inactive(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $data = $this->data();
        unset($data['criteria']['citizenship']);
        $data['source_status'] = 'unreviewed';
        $this->post('/admin/scholarships', $data)->assertSessionHasErrors(['criteria.citizenship.mode', 'source_status']);
        $data['status'] = 'inactive';
        $this->post('/admin/scholarships', $data)->assertRedirect();
        $this->assertSame('inactive', Scholarship::firstOrFail()->status);
    }

    public function test_invalid_restricted_sets_urls_and_route_thresholds_are_rejected(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $data = $this->data();
        $data['criteria']['citizenship'] = ['mode' => 'restricted', 'values' => []];
        $data['criteria']['academic'] = ['mode' => 'restricted'];
        $data['academic_routes'] = [['qualification' => 'Diploma', 'completion_status' => 'completed', 'result_type' => 'cgpa', 'minimum' => 4.1, 'grading_scale' => 4]];
        $data['application_url'] = 'javascript:alert(1)';
        $this->post('/admin/scholarships', $data)->assertSessionHasErrors(['criteria.citizenship.values', 'academic_routes.0.minimum', 'application_url']);
        $this->assertDatabaseCount('scholarships', 0);
    }

    public function test_students_cannot_mutate_scholarships_and_admin_can_deactivate_and_delete(): void
    {
        $s = Scholarship::factory()->create();
        $this->actingAs(User::factory()->create())->put('/admin/scholarships/'.$s->id, $this->data())->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->patch('/admin/scholarships/'.$s->id.'/deactivate')->assertRedirect();
        $this->assertSame('inactive', $s->fresh()->status);
        $this->delete('/admin/scholarships/'.$s->id)->assertRedirect();
        $this->assertDatabaseMissing('scholarships', ['id' => $s->id]);
    }

    public function test_deadlines_close_after_local_date_or_recorded_provider_time(): void
    {
        $s = Scholarship::factory()->create(['deadline' => '2026-10-12']);
        $this->travelTo(CarbonImmutable::parse('2026-10-12 23:59:59', 'Asia/Kuala_Lumpur'));
        $this->assertSame('open', $s->effectiveStatus());
        $this->travelTo(CarbonImmutable::parse('2026-10-13 00:00:00', 'Asia/Kuala_Lumpur'));
        $this->assertSame('closed', $s->effectiveStatus());
        $s->closing_at = '2026-10-12 09:00:00';
        $this->travelTo(CarbonImmutable::parse('2026-10-12 17:00:00', 'Asia/Kuala_Lumpur'));
        $this->assertSame('open', $s->effectiveStatus());
        $this->travel(1)->seconds();
        $this->assertSame('closed', $s->effectiveStatus());
    }
}
