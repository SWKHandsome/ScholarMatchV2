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

    /**
     * @return array{name: string, provider: string, benefits: string, deadline: string, scholarship_url: string, source_status: string, status: string, criteria: array<string, array{mode: string}>, income_requirement: string}
     */
    private function scholarshipData(): array
    {
        return ['name' => 'Demo Category Award', 'provider' => 'Demo Provider', 'benefits' => 'Demo tuition', 'deadline' => '2027-01-31', 'scholarship_url' => 'https://example.test/scholarship', 'source_status' => 'reviewed', 'status' => 'open', 'criteria' => array_fill_keys(array_keys(config('scholarmatch.criteria')), ['mode' => 'none']), 'income_requirement' => 'categories'];
    }

    public function test_simplified_category_selection_fills_the_reference_year_and_matches_students(): void
    {
        $category = IncomeCategory::factory()->create(['name' => 'B40', 'reference_year' => 2025, 'maximum' => 4999.99]);
        IncomeCategory::factory()->create(['name' => 'Upper', 'reference_year' => 2025, 'minimum' => 5000]);
        IncomeCategory::factory()->create(['name' => 'B40', 'reference_year' => 2026]);
        $data = $this->scholarshipData();
        $data['criteria']['income'] = ['category_ids' => [(string) $category->id]];

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post('/admin/scholarships', $data)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $scholarship = Scholarship::withEvaluationData()->firstOrFail();
        $this->assertSame(2025, $scholarship->requirements->criteria['income']['reference_year']);
        $this->assertSame([$category->id], $scholarship->incomeCategories->modelKeys());

        $profile = StudentProfile::factory()->create(['household_income' => 4000]);
        $this->assertSame('Eligible', app(ScholarshipEligibilityService::class)->evaluate($profile, $scholarship, IncomeCategory::all())->status->value);
        $profile->household_income = 5000;
        $this->assertSame('Not Eligible', app(ScholarshipEligibilityService::class)->evaluate($profile, $scholarship, IncomeCategory::all())->status->value);
        $this->get('/admin/scholarships/'.$scholarship->id.'/edit')->assertSee('Using configured categories for 2025.');
    }

    public function test_category_selection_requires_active_categories_from_one_year(): void
    {
        $category = IncomeCategory::factory()->create();
        $otherYear = IncomeCategory::factory()->create(['reference_year' => 2025]);
        $inactive = IncomeCategory::factory()->create(['is_active' => false]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        foreach ([[], [$category->id, $otherYear->id], [$inactive->id], [999999]] as $categoryIds) {
            $data = $this->scholarshipData();
            $data['criteria']['income'] = ['category_ids' => $categoryIds];
            $this->post('/admin/scholarships', $data)->assertSessionHasErrors('criteria.income.category_ids');
        }
        $this->assertDatabaseCount('scholarships', 0);
    }

    public function test_no_income_restriction_clears_previously_selected_categories(): void
    {
        $category = IncomeCategory::factory()->create();
        $scholarship = Scholarship::factory()->create();
        $criteria = $scholarship->requirements->criteria;
        $criteria['income'] = ['mode' => 'restricted', 'kind' => 'categories', 'reference_year' => 2026, 'category_ids' => [$category->id]];
        $scholarship->requirements->update(['criteria' => $criteria]);
        $scholarship->incomeCategories()->attach($category);
        $data = $this->scholarshipData();
        $data['income_requirement'] = 'none';
        $data['criteria']['income'] = ['category_ids' => [$category->id]];

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->put('/admin/scholarships/'.$scholarship->id, $data)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame(['mode' => 'none'], $scholarship->requirements->fresh()->criteria['income']);
        $this->assertDatabaseCount('scholarship_income_categories', 0);
    }

    public function test_existing_numeric_and_manual_income_rules_are_preserved_when_editing_basic_details(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        foreach ([['mode' => 'restricted', 'kind' => 'numeric', 'maximum' => 5000], ['mode' => 'manual', 'notes' => 'Verify variable income.']] as $incomeRule) {
            $scholarship = Scholarship::factory()->create();
            $criteria = $scholarship->requirements->criteria;
            $criteria['income'] = $incomeRule;
            $scholarship->requirements->update(['criteria' => $criteria]);
            $this->get('/admin/scholarships/'.$scholarship->id.'/edit')->assertOk()->assertSee($incomeRule['mode'] === 'manual' ? 'Verify variable income.' : 'Keep saved income requirement');
            $data = $this->scholarshipData();
            $data['income_requirement'] = 'existing';
            $this->put('/admin/scholarships/'.$scholarship->id, $data)->assertSessionHasNoErrors()->assertRedirect();
            $this->assertEquals($incomeRule, $scholarship->requirements->fresh()->criteria['income']);
        }
    }

    public function test_a_new_scholarship_cannot_choose_to_keep_a_nonexistent_income_rule(): void
    {
        $data = $this->scholarshipData();
        $data['income_requirement'] = 'existing';
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post('/admin/scholarships', $data)
            ->assertSessionHasErrors('income_requirement');
        $this->assertDatabaseCount('scholarships', 0);
    }

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
