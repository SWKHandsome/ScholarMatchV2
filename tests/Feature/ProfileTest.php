<?php

namespace Tests\Feature;

use App\Models\StudentAcademicRecord;
use App\Models\StudentProfile;
use App\Models\StudyField;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function data(): array
    {
        return ['citizenship' => 'Malaysian', 'residence_state' => 'Selangor', 'current_education_level' => 'secondary', 'funding_level' => 'bachelor', 'institution_type' => 'undecided', 'study_location' => 'undecided', 'study_field_undecided' => true, 'household_income' => 0, 'academic_records_complete' => true, 'academic_records' => [
            ['qualification' => 'SPM', 'completion_status' => 'completed', 'result_type' => 'as_count', 'result_value' => 8, 'grading_scale' => 10],
            ['qualification' => 'Diploma', 'completion_status' => 'in_progress', 'result_type' => 'cgpa', 'result_value' => 3.8, 'grading_scale' => 4],
        ]];
    }

    public function test_residence_options_cover_malaysia_and_reject_places_outside_it(): void
    {
        $this->assertSame(['Johor', 'Kedah', 'Kelantan', 'Melaka', 'Negeri Sembilan', 'Pahang', 'Penang', 'Perak', 'Perlis', 'Sabah', 'Sarawak', 'Selangor', 'Terengganu', 'Kuala Lumpur', 'Labuan', 'Putrajaya'], config('scholarmatch.residence_state'));
        $user = User::factory()->create();
        $this->actingAs($user)->get('/profile')->assertOk()->assertSee('State / federal territory of residence')->assertSee('Pulau Pinang (Penang)')->assertSee('Labuan')->assertSee('Putrajaya');
        foreach (['Penang', 'Kuala Lumpur', 'Labuan', 'Putrajaya'] as $state) {
            $this->put('/profile', ['residence_state' => $state])->assertSessionHasNoErrors()->assertRedirect();
            $this->assertSame($state, $user->profile()->firstOrFail()->residence_state);
        }
        $this->put('/profile', ['residence_state' => 'Singapore'])->assertSessionHasErrors('residence_state');
        $this->assertSame('Putrajaya', $user->profile()->firstOrFail()->residence_state);
    }

    public function test_cgpa_and_as_count_results_save_without_scale_inputs(): void
    {
        $user = User::factory()->create();
        $data = $this->data();
        unset($data['academic_records'][0]['grading_scale'], $data['academic_records'][1]['grading_scale']);

        $this->actingAs($user)->put('/profile', $data)->assertSessionHasNoErrors()->assertRedirect();
        $records = $user->profile()->firstOrFail()->academicRecords;
        $this->assertSame('4.0000', $records->firstWhere('result_type', 'cgpa')->grading_scale);
        $this->assertSame('8.0000', $records->firstWhere('result_type', 'as_count')->result_value);
        $this->assertNull($records->firstWhere('result_type', 'as_count')->grading_scale);
        $this->get('/profile')->assertOk()->assertSee('CGPA (out of 4.00)')->assertDontSee('Grading scale maximum')->assertDontSee('[grading_scale]', false);
    }

    public function test_qualification_infers_result_format_without_completion_enrolment_or_result_type_inputs(): void
    {
        $cases = ['SPM' => ['as_count', 8], 'UEC' => ['as_count', 5], 'A Level' => ['as_count', 3], 'STPM' => ['cgpa', 3.7], 'Matriculation' => ['cgpa', 3.8], 'Foundation' => ['cgpa', 3.6], 'Diploma' => ['cgpa', 3.5], 'Bachelor' => ['cgpa', 3.4], 'Master' => ['cgpa', 3.9], 'Other' => ['other', null]];
        $data = $this->data();
        $data['academic_records'] = [];
        foreach ($cases as $qualification => [$resultType, $result]) {
            $data['academic_records'][] = ['qualification' => $qualification, 'result_value' => $result, 'result_notes' => $resultType === 'other' ? 'Provider should review my certificate.' : null];
        }
        $this->actingAs($user = User::factory()->create())->put('/profile', $data)->assertSessionHasNoErrors()->assertRedirect();
        $profile = $user->profile()->firstOrFail();
        $this->assertNull($profile->enrolment_status);
        $this->assertSame(100, $profile->completeness());
        $this->assertNotContains('enrolment status', $profile->missingInformation());
        foreach ($profile->academicRecords as $record) {
            $this->assertSame($cases[$record->qualification][0], $record->result_type);
            $this->assertSame($record->result_type === 'cgpa' ? '4.0000' : null, $record->grading_scale);
        }
        $this->get('/profile')->assertOk()->assertDontSee('Completion status')->assertDontSee('Enrolment status')->assertDontSee('<select id="academic_records[0][result_type]"', false);
    }

    public function test_legacy_result_format_is_preserved_until_the_qualification_changes(): void
    {
        $profile = StudentProfile::factory()->create();
        $record = StudentAcademicRecord::factory()->create(['student_profile_id' => $profile->id, 'qualification' => 'STPM', 'result_type' => 'as_count', 'result_value' => 5, 'grading_scale' => null]);
        $this->actingAs(User::findOrFail($profile->user_id))->get('/profile')->assertOk()->assertSee('Saved result format: Number of As.');
        $data = ['academic_records' => [['id' => $record->id, 'qualification' => 'STPM', 'result_type' => 'as_count', 'result_value' => 5]]];
        $this->put('/profile', $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('as_count', $record->fresh()->result_type);
        $this->assertSame('5.0000', $record->fresh()->result_value);
        $data['academic_records'][0]['qualification'] = 'Diploma';
        $data['academic_records'][0]['result_value'] = 3.8;
        $this->put('/profile', $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('cgpa', $record->fresh()->result_type);
        $this->assertSame('4.0000', $record->fresh()->grading_scale);
    }

    public function test_cgpa_cannot_exceed_four_even_when_a_different_scale_is_submitted(): void
    {
        $user = User::factory()->create();
        $data = $this->data();
        $data['academic_records'][1]['grading_scale'] = 5;
        $data['academic_records'][1]['result_value'] = 4.01;

        $this->actingAs($user)->put('/profile', $data)->assertSessionHasErrors('academic_records.1.result_value');
        $this->assertDatabaseCount('student_academic_records', 0);
        $data['academic_records'][1]['result_value'] = 4;
        $this->put('/profile', $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('4.0000', $user->profile()->firstOrFail()->academicRecords->firstWhere('result_type', 'cgpa')->grading_scale);
    }

    public function test_profile_supports_multiple_qualifications_zero_income_and_intended_funding(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->put('/profile', $this->data())->assertRedirect();
        $profile = $user->profile()->firstOrFail();
        $this->assertSame('0.00', $profile->household_income);
        $this->assertSame('secondary', $profile->current_education_level);
        $this->assertSame('bachelor', $profile->funding_level);
        $this->assertSame(100, $profile->completeness());
        $this->assertCount(2, $profile->academicRecords);
        $this->get('/profile')->assertOk()->assertSee('Programme seeking funding')->assertSee('Add Qualification');
    }

    public function test_partial_profiles_save_and_missing_income_differs_from_zero(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->put('/profile', ['citizenship' => 'Malaysian'])->assertRedirect();
        $profile = $user->profile()->firstOrFail();
        $this->assertNull($profile->household_income);
        $this->assertSame(11, $profile->completeness());
        $this->assertContains('household income', $profile->missingInformation());
    }

    public function test_invalid_results_income_and_enums_are_rejected_atomically(): void
    {
        $this->actingAs(User::factory()->create());
        $data = $this->data();
        $data['household_income'] = -1;
        $data['citizenship'] = 'invented';
        $data['academic_records'][0]['result_value'] = 8.5;
        $data['academic_records'][1]['result_value'] = 4.1;
        $this->put('/profile', $data)->assertSessionHasErrors(['household_income', 'citizenship', 'academic_records.0.result_value', 'academic_records.1.result_value']);
        $this->assertDatabaseCount('student_academic_records', 0);
        $this->assertDatabaseCount('student_profiles', 0);
    }

    public function test_students_cannot_edit_foreign_records_and_administrators_cannot_edit_profiles(): void
    {
        $record = StudentAcademicRecord::factory()->create();
        $data = $this->data();
        $data['user_id'] = $record->studentProfile->user_id;
        $data['academic_records'][0]['id'] = $record->id;
        $this->actingAs(User::factory()->create())->put('/profile', $data)->assertSessionHasErrors('academic_records.0.id');
        $this->assertSame('3.8000', $record->fresh()->result_value);
        $this->actingAs(User::factory()->create(['role' => 'admin']))->put('/profile', $this->data())->assertForbidden();
    }

    public function test_inactive_fields_can_be_retained_but_not_newly_selected(): void
    {
        $profile = StudentProfile::factory()->create();
        $profile->studyField->update(['is_active' => false]);
        $this->actingAs(User::findOrFail($profile->user_id));
        $data = $this->data();
        $data['study_field_undecided'] = false;
        $data['study_field_id'] = $profile->study_field_id;
        $this->put('/profile', $data)->assertRedirect();
        $this->get('/profile')->assertSee('needs replacement');
        $data['study_field_id'] = StudyField::factory()->create(['is_active' => false])->id;
        $this->put('/profile', $data)->assertSessionHasErrors('study_field_id');
    }

    public function test_owned_records_can_be_updated_and_removed_including_all_records(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->put('/profile', $this->data())->assertRedirect();
        $record = $user->profile->academicRecords->first();
        $data = $this->data();
        $data['academic_records'] = [$data['academic_records'][0] + ['id' => $record->id]];
        $data['academic_records'][0]['result_value'] = 9;
        $this->put('/profile', $data)->assertRedirect();
        $this->assertDatabaseCount('student_academic_records', 1);
        $this->assertSame('9.0000', $record->fresh()->result_value);
        $this->put('/profile', ['academic_records' => ''])->assertRedirect();
        $this->assertDatabaseCount('student_academic_records', 0);
    }
}
