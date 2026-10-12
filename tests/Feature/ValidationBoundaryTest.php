<?php

namespace Tests\Feature;

use App\Models\Scholarship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidationBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_malformed_profile_shapes_return_validation_errors_without_mutating_records(): void
    {
        $this->actingAs(User::factory()->create());
        $this->put('/profile', ['academic_records' => 'invalid', 'study_field_id' => ['unexpected']])->assertSessionHasErrors(['academic_records', 'study_field_id']);
        $this->assertDatabaseCount('student_profiles', 0);
    }

    public function test_malformed_scholarship_criteria_and_routes_return_validation_errors(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->post('/admin/scholarships', ['status' => 'open', 'criteria' => ['study_field' => ['mode' => 'restricted', 'values' => 'invalid']], 'academic_routes' => 'invalid', 'closing_at' => ['invalid']])->assertSessionHasErrors(['criteria.study_field.values', 'academic_routes', 'closing_at']);
        $this->assertDatabaseCount('scholarships', 0);
    }

    public function test_multibyte_password_over_bcrypt_byte_limit_is_rejected_cleanly(): void
    {
        $password = str_repeat('界', 30);
        $this->post('/register', ['name' => 'Student', 'email' => 'unicode@example.test', 'password' => $password, 'password_confirmation' => $password])->assertSessionHasErrors('password');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_scholarship_update_preserves_unrestricted_modes_and_audits_a_new_reviewer(): void
    {
        $s = Scholarship::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $data = ['name' => 'Updated demo', 'provider' => 'Provider', 'description' => 'Updated description', 'benefits' => 'Updated benefits', 'deadline' => '2027-02-01', 'closing_at' => '2027-02-01T17:00', 'application_url' => 'https://example.test/apply', 'source_url' => 'https://example.test/source', 'source_status' => 'reviewed', 'status' => 'open', 'criteria' => $s->requirements->criteria];
        $this->actingAs($admin)->put('/admin/scholarships/'.$s->id, $data)->assertRedirect();
        $this->assertSame('Updated demo', $s->fresh()->name);
        $this->assertSame($admin->id, $s->fresh()->verified_by);
        $this->assertSame('2027-02-01 09:00:00', $s->fresh()->closing_at);
        $this->assertSame('none', $s->requirements->fresh()->criteria['study_field']['mode']);
    }
}
