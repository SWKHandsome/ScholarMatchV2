<?php

namespace Tests\Feature;

use App\Models\Scholarship;
use App\Models\StudentAcademicRecord;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentJourneyTest extends TestCase
{
    use RefreshDatabase;

    private function student(): User
    {
        $p = StudentProfile::factory()->create();
        StudentAcademicRecord::factory()->create(['student_profile_id' => $p->id]);

        return User::findOrFail($p->user_id);
    }

    public function test_dashboard_recommendations_details_and_recorded_application_link(): void
    {
        $u = $this->student();
        $s = Scholarship::factory()->create(['name' => 'Demo Award', 'application_url' => 'https://example.test/exact-destination']);
        $this->actingAs($u)->get('/dashboard')->assertOk()->assertSee('Top recommended scholarships')->assertSee('100%')->assertSee('Demo Award');
        $this->get('/recommendations')->assertOk()->assertSee('Demo Award')->assertSee('Academic margin');
        $r = $this->get('/scholarships/'.$s->id);
        $r->assertOk()->assertSee('Requirement breakdown')->assertSee('href="https://example.test/exact-destination"', false);
        foreach (config('scholarmatch.criteria') as $label) {
            $r->assertSee($label);
        }
    }

    public function test_bookmarks_are_idempotent_owned_and_preserve_closed_or_inactive_opportunities(): void
    {
        $u = $this->student();
        $s = Scholarship::factory()->create();
        $this->actingAs($u)->post('/saved/'.$s->id, ['user_id' => 999])->assertRedirect();
        $this->post('/saved/'.$s->id)->assertRedirect();
        $this->assertDatabaseCount('saved_scholarships', 1);
        $s->update(['status' => 'closed']);
        $this->get('/saved')->assertOk()->assertSee($s->name)->assertSee('Closed');
        $s->update(['status' => 'inactive']);
        $this->get('/saved')->assertOk()->assertSee($s->name);
        $this->get('/scholarships/'.$s->id)->assertOk();
        $this->actingAs($other = $this->student())->delete('/saved/'.$s->id)->assertRedirect();
        $this->assertDatabaseCount('saved_scholarships', 1);
        $this->get('/scholarships/'.$s->id)->assertForbidden();
        $this->post('/saved/'.$s->id)->assertForbidden();
        $this->actingAs($u)->delete('/saved/'.$s->id)->assertRedirect();
        $this->delete('/saved/'.$s->id)->assertRedirect();
        $this->assertDatabaseCount('saved_scholarships', 0);
    }

    public function test_eligibility_updates_on_saved_page_and_details_after_profile_changes(): void
    {
        $u = $this->student();
        $s = Scholarship::factory()->create();
        $s->requirements->update(['criteria' => ['citizenship' => ['mode' => 'restricted', 'values' => ['Malaysian']]] + $s->requirements->criteria]);
        $this->actingAs($u)->post('/saved/'.$s->id)->assertRedirect();
        $this->get('/saved')->assertSee('Eligible');
        $u->profile->update(['citizenship' => 'Non-Malaysian']);
        $this->get('/saved')->assertSee('Not Eligible');
        $this->get('/scholarships/'.$s->id)->assertSee('Not Eligible');
    }

    public function test_filter_validation_empty_results_pagination_and_student_role_authorization(): void
    {
        $this->actingAs($this->student());
        Scholarship::factory()->count(12)->create();
        $this->get('/recommendations?page=2')->assertOk()->assertViewHas('results', fn ($p) => $p->total() === 12 && $p->count() === 2);
        $this->get('/recommendations?provider=Unknown')->assertOk()->assertSee('No opportunities found');
        $this->get('/recommendations?sort=unsupported')->assertSessionHasErrors('sort');
        $this->get('/recommendations?eligibility=Invalid')->assertSessionHasErrors('eligibility');
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/recommendations')->assertForbidden();
        $this->post('/saved/999')->assertForbidden();
    }

    public function test_student_content_is_escaped_and_margin_is_explained(): void
    {
        $u = $this->student();
        $s = Scholarship::factory()->create(['description' => '<script>alert("x")</script>']);
        $s->academicRoutes()->create(['qualification' => 'Diploma', 'completion_status' => 'completed', 'result_type' => 'cgpa', 'minimum' => 3, 'grading_scale' => 4]);
        $s->requirements->update(['criteria' => ['academic' => ['mode' => 'restricted']] + $s->requirements->criteria]);
        $this->actingAs($u)->get('/scholarships/'.$s->id)->assertOk()->assertDontSee('<script>alert("x")</script>', false)->assertSee('&lt;script&gt;', false)->assertSee('26.7%')->assertSee('above the academic minimum')->assertSee('probability');
    }

    public function test_an_incomplete_profile_does_not_block_browsing_and_restricted_information_is_manual(): void
    {
        $u = User::factory()->create();
        $s = Scholarship::factory()->create();
        $s->requirements->update(['criteria' => ['citizenship' => ['mode' => 'restricted', 'values' => ['Malaysian']]] + $s->requirements->criteria]);
        $this->actingAs($u)->get('/dashboard')->assertOk()->assertSee('0%');
        $this->get('/recommendations')->assertOk()->assertSee('Manual Verification Required');
        $this->get('/profile')->assertOk();
    }
}
