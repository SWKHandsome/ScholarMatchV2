<?php

namespace Tests\Feature;

use App\Models\Scholarship;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdministratorDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_counts_effective_status_and_source_reviews_separately(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->count(2)->create();
        Scholarship::factory()->create();
        Scholarship::factory()->create(['deadline' => now()->subDay()->toDateString(), 'last_verified_at' => now()->subDays(100)]);
        Scholarship::factory()->create(['status' => 'inactive']);
        $this->actingAs($admin)->get('/admin')->assertOk()->assertViewHas('stats', fn ($s) => $s['total'] === 3 && $s['open'] === 1 && $s['closed'] === 1 && $s['source_reviews'] === 1 && $s['students'] === 2)->assertSee('Source reviews due');
    }

    public function test_users_page_exposes_completeness_without_sensitive_results_and_can_deactivate_accounts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $p = StudentProfile::factory()->create(['household_income' => 1234.56]);
        $u = User::findOrFail($p->user_id);
        $this->actingAs($admin)->get('/admin/users')->assertOk()->assertSee($u->email)->assertSee('100%')->assertDontSee('1234.56');
        $this->patch('/admin/users/'.$u->id, ['is_active' => false, 'household_income' => 999])->assertRedirect();
        $this->assertFalse($u->fresh()->is_active);
        $this->assertSame('1234.56', $p->fresh()->household_income);
        $this->patch('/admin/users/'.$admin->id, ['is_active' => false])->assertSessionHasErrors('is_active');
        $this->actingAs(User::factory()->create())->get('/admin/users')->assertForbidden();
    }
}
