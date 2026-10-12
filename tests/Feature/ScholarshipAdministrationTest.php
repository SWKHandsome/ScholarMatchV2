<?php

namespace Tests\Feature;

use App\Models\Scholarship;
use App\Models\User;
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
