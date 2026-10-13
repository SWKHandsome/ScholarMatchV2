<?php

namespace Tests\Feature;

use App\Models\IncomeCategory;
use App\Models\StudyField;
use App\Models\User;
use App\Services\IncomeCategoryResolver;
use Database\Seeders\StudyFieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferenceDataTest extends TestCase
{
    use RefreshDatabase;

    private function configuration(): array
    {
        return ['reference_year' => 2026, 'source_url' => 'https://example.test/fictional-income', 'categories' => [['name' => 'B40', 'minimum' => '0', 'maximum' => '4999.99'], ['name' => 'M40', 'minimum' => '5000', 'maximum' => '9999.99'], ['name' => 'T20', 'minimum' => '10000', 'maximum' => null]]];
    }

    public function test_common_study_fields_are_added_without_duplicates_or_reactivating_existing_fields(): void
    {
        $engineering = StudyField::factory()->create(['name' => 'Engineering', 'is_active' => false]);
        $this->seed(StudyFieldSeeder::class);
        $ids = StudyField::orderBy('name')->pluck('id', 'name')->all();
        $this->seed(StudyFieldSeeder::class);
        $this->assertSame($ids, StudyField::orderBy('name')->pluck('id', 'name')->all());
        $this->assertDatabaseCount('study_fields', 25);
        $this->assertSame($engineering->id, StudyField::where('name', 'Engineering')->firstOrFail()->id);
        $this->assertFalse($engineering->fresh()->is_active);
        $this->actingAs(User::factory()->create())->get('/profile')->assertOk()->assertSee('Software Engineering')->assertSee('Accounting')->assertSee('Nursing')->assertSee('Law');
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/admin/scholarships/create')->assertOk()->assertSee('Software Engineering')->assertSee('Accounting')->assertSee('Nursing')->assertSee('Law');
    }

    public function test_complete_income_configuration_and_boundary_resolution(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))->put('/admin/income', $this->configuration())->assertRedirect();
        $resolver = new IncomeCategoryResolver;
        $categories = IncomeCategory::all();
        foreach ([0 => 'B40', '4999.99' => 'B40', 5000 => 'M40', '9999.99' => 'M40', 10000 => 'T20', 999999 => 'T20'] as $income => $name) {
            $this->assertSame($name, $resolver->resolve((float) $income, $categories)?->name);
        }
        $this->assertNull($resolver->resolve(null, $categories));
        $this->get('/admin/income')->assertOk()->assertSee('B40');
    }

    public function test_overlap_gap_negative_bounds_and_missing_upper_range_are_rejected(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        foreach (['4999', '5000.01', '-1'] as $minimum) {
            $data = $this->configuration();
            $data['categories'][1]['minimum'] = $minimum;
            $this->put('/admin/income', $data)->assertSessionHasErrors();
            $this->assertDatabaseCount('income_categories', 0);
        }
        $data = $this->configuration();
        $data['categories'][2]['maximum'] = 20000;
        $this->put('/admin/income', $data)->assertSessionHasErrors('categories');
        $data['source_url'] = 'javascript:alert(1)';
        $this->put('/admin/income', $data)->assertSessionHasErrors('source_url');
    }

    public function test_reference_updates_preserve_ids_and_change_resolution_immediately(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->put('/admin/income', $this->configuration())->assertRedirect();
        $ids = IncomeCategory::orderBy('minimum')->pluck('id')->all();
        $data = $this->configuration();
        $data['categories'][0]['maximum'] = '5999.99';
        $data['categories'][1]['minimum'] = 6000;
        $this->put('/admin/income', $data)->assertRedirect();
        $this->assertSame($ids, IncomeCategory::orderBy('minimum')->pluck('id')->all());
        $this->assertSame('B40', (new IncomeCategoryResolver)->resolve(5500, IncomeCategory::all())->name);
    }

    public function test_study_fields_are_controlled_and_deactivation_preserves_records(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->post('/admin/fields', ['name' => 'Engineering'])->assertRedirect();
        $field = StudyField::firstOrFail();
        $this->post('/admin/fields', ['name' => 'Engineering'])->assertSessionHasErrors('name');
        $this->put('/admin/fields/'.$field->id, ['name' => 'Engineering', 'is_active' => false])->assertRedirect();
        $this->assertFalse($field->fresh()->is_active);
        $this->get('/admin/fields')->assertOk()->assertSee('Engineering');
        $this->actingAs(User::factory()->create())->post('/admin/fields', ['name' => 'Medicine'])->assertForbidden();
    }
}
