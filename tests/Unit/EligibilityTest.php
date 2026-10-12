<?php

namespace Tests\Unit;

use App\Data\EvaluationResult;
use App\Enums\CriterionStatus as C;
use App\Enums\EligibilityStatus as E;
use App\Models\IncomeCategory;
use App\Models\Scholarship;
use App\Models\ScholarshipAcademicRoute;
use App\Models\ScholarshipRequirement;
use App\Models\StudentAcademicRecord;
use App\Models\StudentProfile;
use App\Models\StudyField;
use App\Services\ScholarshipEligibilityService;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EligibilityTest extends TestCase
{
    private function profile(array $attributes = [], array $records = []): StudentProfile
    {
        $p = new StudentProfile($attributes + ['citizenship' => 'Malaysian', 'funding_level' => 'bachelor', 'current_education_level' => 'secondary', 'enrolment_status' => 'planning', 'institution_type' => 'public_university', 'study_location' => 'Malaysia', 'residence_state' => 'Selangor', 'household_income' => 0, 'academic_records_complete' => true, 'study_field_id' => 1]);
        $p->setRelation('academicRecords', collect(array_map(fn ($r) => new StudentAcademicRecord($r + ['qualification' => 'Diploma', 'completion_status' => 'completed', 'result_type' => 'cgpa', 'result_value' => 3.8, 'grading_scale' => 4]), $records)));
        $p->setRelation('studyField', new StudyField(['id' => 1, 'name' => 'Engineering', 'is_active' => true]));

        return $p;
    }

    private function scholarship(array $criteria = [], array $routes = []): Scholarship
    {
        $s = new Scholarship;
        $s->setRelation('requirements', new ScholarshipRequirement(['criteria' => $criteria + array_fill_keys(array_keys(config('scholarmatch.criteria')), ['mode' => 'none'])]));
        $s->setRelation('academicRoutes', collect(array_map(fn ($r) => new ScholarshipAcademicRoute($r + ['id' => 1, 'qualification' => 'Diploma', 'completion_status' => 'completed', 'result_type' => 'cgpa', 'minimum' => 3.5, 'grading_scale' => 4]), $routes)));
        $s->setRelation('studyFields', collect());
        $s->setRelation('incomeCategories', collect());

        return $s;
    }

    private function evaluate(StudentProfile $p, Scholarship $s): EvaluationResult
    {
        return app(ScholarshipEligibilityService::class)->evaluate($p, $s, collect());
    }

    public static function criterionCases(): array
    {
        return [
            'prospective bachelor' => ['funding_level', 'bachelor', ['bachelor'], E::Eligible, C::Met],
            'offer required' => ['enrolment_status', 'planning', ['offer_received', 'enrolled'], E::NotEligible, C::NotMet],
            'offer accepted' => ['enrolment_status', 'offer_received', ['offer_received', 'enrolled'], E::Eligible, C::Met],
            'enrolled only' => ['enrolment_status', 'offer_received', ['enrolled'], E::NotEligible, C::NotMet],
            'citizenship fails' => ['citizenship', 'Non-Malaysian', ['Malaysian'], E::NotEligible, C::NotMet],
            'missing citizenship' => ['citizenship', null, ['Malaysian'], E::Manual, C::Manual],
            'unknown institution' => ['institution_type', 'undecided', ['public_university'], E::Manual, C::Manual],
            'location fails' => ['study_location', 'overseas', ['Malaysia'], E::NotEligible, C::NotMet],
            'unknown location' => ['study_location', 'undecided', ['Malaysia'], E::Manual, C::Manual],
            'residence passes' => ['residence_state', 'Selangor', ['Selangor'], E::Eligible, C::Met],
            'empty restricted set' => ['citizenship', 'Malaysian', [], E::Manual, C::Manual],
        ];
    }

    #[DataProvider('criterionCases')]
    public function test_set_criteria(string $key, ?string $value, array $accepted, E $overall, C $criterion): void
    {
        $r = $this->evaluate($this->profile([$key => $value]), $this->scholarship([$key => ['mode' => 'restricted', 'values' => $accepted]]));
        $this->assertSame($overall, $r->status);
        $this->assertSame($criterion, $r->criteria[$key]->status);
        $this->assertNotEmpty($r->criteria[$key]->explanation);
    }

    public function test_unrestricted_missing_information_is_na_and_unreviewed_requirement_is_manual(): void
    {
        $r = app(ScholarshipEligibilityService::class)->evaluate(null, $this->scholarship(), collect());
        $this->assertSame(E::Eligible, $r->status);
        $this->assertNull($r->academicMargin);
        foreach ($r->criteria as $c) {
            $this->assertSame(C::NotApplicable, $c->status);
            $this->assertNotEmpty($c->explanation);
        }
        $s = $this->scholarship(['citizenship' => []]);
        $this->assertSame(E::Manual, $this->evaluate($this->profile(), $s)->status);
    }

    public function test_definite_failure_overrides_unresolved_criteria_and_has_no_margin(): void
    {
        $r = $this->evaluate($this->profile(['citizenship' => 'Non-Malaysian'], [[]]), $this->scholarship(['academic' => ['mode' => 'restricted'], 'citizenship' => ['mode' => 'restricted', 'values' => ['Malaysian']], 'additional' => ['mode' => 'manual', 'notes' => 'Interview']], [[]]));
        $this->assertSame(E::NotEligible, $r->status);
        $this->assertNull($r->academicMargin);
        $this->assertSame(C::Manual, $r->criteria['additional']->status);
    }

    public static function academicCases(): array
    {
        return [
            'full pass' => [[[]], [[]], true, E::Eligible, ((3.8 / 3.5) - 1) * 100],
            'exact minimum' => [[['result_value' => 3.5]], [[]], true, E::Eligible, 0.0],
            'zero minimum' => [[[]], [['minimum' => 0]], true, E::Eligible, null],
            'above one hundred percent' => [[['qualification' => 'SPM', 'result_type' => 'as_count', 'result_value' => 9, 'grading_scale' => 10]], [['qualification' => 'SPM', 'result_type' => 'as_count', 'minimum' => 3, 'grading_scale' => 10]], true, E::Eligible, 200.0],
            'below minimum' => [[['result_value' => 3.4]], [[]], true, E::NotEligible, null],
            'absent complete' => [[], [[]], true, E::NotEligible, null],
            'absent incomplete' => [[], [[]], false, E::Manual, null],
            'failed but incomplete' => [[['result_value' => 3.4]], [[]], false, E::Manual, null],
            'incompatible scales' => [[['grading_scale' => 5]], [[]], true, E::Manual, null],
            'unknown result' => [[['result_value' => null]], [[]], true, E::Manual, null],
            'unsupported result' => [[['result_type' => 'other']], [[]], true, E::Manual, null],
            'in progress completed needed' => [[['completion_status' => 'in_progress']], [[]], true, E::NotEligible, null],
            'in progress accepted' => [[['completion_status' => 'in_progress']], [['completion_status' => 'in_progress']], true, E::Eligible, ((3.8 / 3.5) - 1) * 100],
            'unresolved subjects' => [[[]], [['manual_conditions' => 'A in Mathematics']], true, E::Manual, null],
            'subject unresolved with failed result' => [[['result_value' => 3.1]], [['manual_conditions' => 'A in Mathematics']], true, E::NotEligible, null],
            'failed SPM successful diploma' => [[[]], [['qualification' => 'SPM', 'result_type' => 'as_count', 'minimum' => 8, 'grading_scale' => 10], []], true, E::Eligible, ((3.8 / 3.5) - 1) * 100],
            'multiple satisfied routes choose highest' => [[[]], [[], ['minimum' => 3]], true, E::Eligible, ((3.8 / 3) - 1) * 100],
            'unresolved route cannot score' => [[[]], [['minimum' => 3, 'manual_conditions' => 'Interview'], []], true, E::Eligible, ((3.8 / 3.5) - 1) * 100],
            'multiple records choose passing' => [[['result_value' => 3.0], ['result_value' => 3.8]], [[]], true, E::Eligible, ((3.8 / 3.5) - 1) * 100],
            'failed and incompatible route' => [[['qualification' => 'SPM', 'result_type' => 'as_count', 'result_value' => 7, 'grading_scale' => 10], ['grading_scale' => 5]], [['qualification' => 'SPM', 'result_type' => 'as_count', 'minimum' => 8, 'grading_scale' => 10], []], true, E::Manual, null],
        ];
    }

    #[DataProvider('academicCases')]
    public function test_academic_alternatives_and_margins(array $records, array $routes, bool $complete, E $status, ?float $margin): void
    {
        $r = $this->evaluate($this->profile(['academic_records_complete' => $complete], $records), $this->scholarship(['academic' => ['mode' => 'restricted']], $routes));
        $this->assertSame($status, $r->status);
        $this->assertSame($margin, $r->academicMargin);
        $this->assertCount(count($routes), $r->academicRoutes);
        if ($margin !== null) {
            $this->assertNotNull($r->rankingRoute);
        }
    }

    public static function incomeCases(): array
    {
        return ['zero' => [0, E::Eligible], 'boundary' => [5000, E::Eligible], 'outside' => [5000.01, E::NotEligible], 'missing' => [null, E::Manual]];
    }

    #[DataProvider('incomeCases')]
    public function test_numeric_income_boundaries(?float $income, E $status): void
    {
        $this->assertSame($status, $this->evaluate($this->profile(['household_income' => $income]), $this->scholarship(['income' => ['mode' => 'restricted', 'kind' => 'numeric', 'maximum' => 5000]]))->status);
    }

    public function test_income_category_reference_changes_take_effect_and_inactive_configuration_is_unresolved(): void
    {
        $category = new IncomeCategory(['id' => 1, 'name' => 'B40', 'reference_year' => 2026, 'minimum' => 0, 'maximum' => 4999.99, 'is_active' => true]);
        $other = new IncomeCategory(['id' => 2, 'name' => 'M40', 'reference_year' => 2026, 'minimum' => 5000, 'maximum' => null, 'is_active' => true]);
        $s = $this->scholarship(['income' => ['mode' => 'restricted', 'kind' => 'categories', 'reference_year' => 2026]]);
        $s->setRelation('incomeCategories', collect([$category]));
        $service = app(ScholarshipEligibilityService::class);
        $p = $this->profile(['household_income' => 5000]);
        $this->assertSame(E::NotEligible, $service->evaluate($p, $s, collect([$category, $other]))->status);
        $category->maximum = 5999.99;
        $other->minimum = 6000;
        $this->assertSame(E::Eligible, $service->evaluate($p, $s, collect([$category, $other]))->status);
        $category->is_active = false;
        $this->assertSame(E::Manual, $service->evaluate($p, $s, collect([$category, $other]))->status);
    }

    public function test_field_restrictions_and_no_hidden_database_queries(): void
    {
        $p = $this->profile();
        $s = $this->scholarship(['study_field' => ['mode' => 'restricted']]);
        $s->setRelation('studyFields', collect([$p->studyField]));
        DB::enableQueryLog();
        $this->assertSame(E::Eligible, $this->evaluate($p, $s)->status);
        $this->assertSame([], DB::getQueryLog());
        $p->studyField->is_active = false;
        $this->assertSame(E::Manual, $this->evaluate($p, $s)->status);
    }
}
