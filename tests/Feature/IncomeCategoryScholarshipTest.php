<?php

namespace Tests\Feature;

use App\Models\IncomeCategory;
use App\Models\Scholarship;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\ScholarshipEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncomeCategoryScholarshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_rules_from_string_form_values_are_evaluated_and_reference_changes_are_immediate(): void
    {
        $category = IncomeCategory::factory()->create(['name' => 'Demo B40', 'maximum' => 4999.99]);
        $upper = IncomeCategory::factory()->create(['name' => 'Demo Upper', 'minimum' => 5000]);
        $admin = User::factory()->create(['role' => 'admin']);
        $criteria = array_fill_keys(array_keys(config('scholarmatch.criteria')), ['mode' => 'none']);
        $criteria['income'] = ['mode' => 'restricted', 'kind' => 'categories', 'reference_year' => '2026', 'category_ids' => [(string) $category->id]];
        $data = ['name' => 'Demo Category Award', 'provider' => 'Demo Provider', 'description' => 'Demo description', 'benefits' => 'Demo tuition', 'deadline' => '2027-01-31', 'application_url' => 'https://example.test/apply', 'source_url' => 'https://example.test/source', 'source_status' => 'reviewed', 'status' => 'open', 'criteria' => $criteria];
        $this->actingAs($admin)->post('/admin/scholarships', $data)->assertRedirect();
        $s = Scholarship::withEvaluationData()->firstOrFail();
        $p = StudentProfile::factory()->create(['household_income' => 5000]);
        $p->load(['studyField', 'academicRecords']);
        $service = app(ScholarshipEligibilityService::class);
        $this->assertSame('Not Eligible', $service->evaluate($p, $s, IncomeCategory::all())->status->value);
        $category->update(['maximum' => 5999.99]);
        $upper->update(['minimum' => 6000]);
        $s->load('incomeCategories');
        $this->assertSame('Eligible', $service->evaluate($p, $s, IncomeCategory::all())->status->value);
        $this->assertDatabaseCount('scholarship_income_categories', 1);
    }
}
