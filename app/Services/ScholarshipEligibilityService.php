<?php

namespace App\Services;

use App\Data\CriterionResult;
use App\Data\EvaluationResult;
use App\Enums\CriterionStatus as C;
use App\Enums\EligibilityStatus as E;
use App\Models\Scholarship;
use App\Models\ScholarshipAcademicRoute;
use App\Models\StudentProfile;
use Illuminate\Support\Collection;

class ScholarshipEligibilityService
{
    public function __construct(private IncomeCategoryResolver $incomeResolver, private AcademicMarginCalculator $marginCalculator) {}

    public function evaluate(?StudentProfile $profile, Scholarship $scholarship, Collection $incomeCategories): EvaluationResult
    {
        $criteria = [];
        $routes = [];
        $rules = $scholarship->requirements?->criteria ?? [];
        foreach (config('scholarmatch.criteria') as $key => $label) {
            $rule = $rules[$key] ?? [];
            $mode = $rule['mode'] ?? null;
            if ($mode === 'none') {
                $criteria[$key] = new CriterionResult($key, $label, C::NotApplicable, 'No restriction', 'Not needed', 'The reviewed scholarship information imposes no '.$label.' restriction.');

                continue;
            }
            if ($mode === 'manual' || $mode !== 'restricted') {
                $criteria[$key] = new CriterionResult($key, $label, C::Manual, $rule['notes'] ?? 'Requirement not yet reviewed', 'Not automatically assessable', 'This condition requires verification: '.($rule['notes'] ?? 'The requirement configuration is incomplete.'), 'Check this condition with the provider.');

                continue;
            }
            if ($key === 'academic') {
                $routes = $scholarship->academicRoutes->map(fn ($route) => $this->academicRoute($profile, $route))->all();
                $states = array_column($routes, 'status');
                $state = in_array(C::Met, $states, true) ? C::Met : ((! $routes || ! $profile?->academic_records_complete || in_array(C::Manual, $states, true)) ? C::Manual : C::NotMet);
                $criteria[$key] = new CriterionResult($key, $label, $state, implode(' OR ', array_column($routes, 'requirement')) ?: 'No configured routes', implode('; ', array_column($routes, 'student')) ?: 'No academic information', $state === C::Met ? 'At least one complete alternative academic route is satisfied.' : ($state === C::NotMet ? 'Your complete academic information confirms every accepted route fails.' : 'No accepted route is confirmed. Relevant academic information or conditions remain unresolved.'), $state === C::Manual ? 'Complete your academic records and verify unresolved route conditions with the provider.' : null);
            } elseif ($key === 'income') {
                $criteria[$key] = $this->income($profile, $scholarship, $rule, $incomeCategories);
            } elseif ($key === 'study_field') {
                $values = $scholarship->studyFields->pluck('id')->all();
                $required = $scholarship->studyFields->pluck('name')->implode(', ');
                $value = $profile?->study_field_id;
                $unknown = ! $value || $profile?->study_field_undecided || ! $profile?->studyField?->is_active;
                $state = ! $values || $unknown ? C::Manual : (in_array($value, $values) ? C::Met : C::NotMet);
                $criteria[$key] = new CriterionResult($key, $label, $state, $required ?: 'Accepted fields not configured', $profile?->studyField?->name ?? 'Missing or undecided', $state === C::Manual ? 'Your field or the accepted-field configuration requires verification.' : 'Your field '.($state === C::Met ? 'is' : 'is not').' in the accepted field list.', $state === C::Manual ? 'Select an active study field in My Profile or check with the provider.' : null);
            } elseif ($key === 'additional') {
                $criteria[$key] = new CriterionResult($key, $label, C::Manual, $rule['notes'] ?? 'Additional condition', 'Not assessable', 'Additional conditions need provider verification.', 'Check with the provider.');
            } else {
                $value = $profile?->$key;
                $values = $rule['values'] ?? [];
                $state = (! $values || $value === null || $value === '' || $value === 'undecided') ? C::Manual : (in_array($value, $values, true) ? C::Met : C::NotMet);
                $required = implode(', ', array_map(fn ($v) => config('scholarmatch.labels.'.$v, $v), $values));
                $student = $value === null ? 'Missing' : config('scholarmatch.labels.'.$value, $value);
                $criteria[$key] = new CriterionResult($key, $label, $state, $required ?: 'Accepted values not configured', $student, $state === C::Manual ? 'Your '.$label.' or the scholarship requirement is missing or undecided.' : $student.($state === C::Met ? ' satisfies ' : ' does not satisfy ').'the accepted '.$label.' values: '.$required.'.', $state === C::Manual ? 'Update '.$label.' in My Profile; check unclear requirements with the provider.' : null);
            }
        }
        $states = array_map(fn ($c) => $c->status, $criteria);
        $overall = in_array(C::NotMet, $states, true) ? E::NotEligible : (in_array(C::Manual, $states, true) ? E::Manual : E::Eligible);
        $best = $overall === E::Eligible ? $this->marginCalculator->best($routes) : null;

        return new EvaluationResult($overall, $criteria, $routes, $best, $best['margin'] ?? null);
    }

    private function academicRoute(?StudentProfile $profile, ScholarshipAcademicRoute $route): array
    {
        $base = ['id' => $route->id, 'qualification' => $route->qualification, 'requirement' => $route->requirementSummary(), 'result_requirement' => $route->resultRequirement(), 'manual_conditions' => $route->manual_conditions, 'minimum' => $route->minimum === null ? null : (float) $route->minimum, 'result' => null, 'student' => 'No matching qualification recorded', 'explanation' => '', 'status' => C::Manual];
        $records = $profile?->academicRecords->where('qualification', $route->qualification) ?? collect();
        if ($records->isEmpty()) {
            return array_replace($base, ['status' => $profile?->academic_records_complete ? C::NotMet : C::Manual, 'explanation' => $profile?->academic_records_complete ? 'Confirmed academic records contain no accepted qualification.' : 'Add the qualification or confirm your academic records are complete.']);
        }
        $outcomes = [];
        foreach ($records as $record) {
            $state = C::Met;
            $reason = 'Your recorded result meets the minimum requirement.';
            $result = $record->result_value === null ? 'unknown result' : (float) $record->result_value;
            $student = $record->qualification.' · '.$result.($record->result_type === 'as_count' ? ($record->result_value === null ? '' : ' As') : ' / '.($record->grading_scale === null ? 'unknown scale' : (float) $record->grading_scale));
            $comparable = in_array($route->result_type, ['cgpa', 'as_count'], true) && $route->qualification !== 'Other' && $record->result_type === $route->result_type && $record->result_value !== null && $route->minimum !== null && ($route->result_type === 'as_count' || ($record->grading_scale !== null && $route->grading_scale !== null && (float) $record->grading_scale === (float) $route->grading_scale));
            if ($comparable && (float) $record->result_value < (float) $route->minimum) {
                $state = C::NotMet;
                $reason = 'Your recorded result is below the required minimum.';
            } elseif (! $comparable || $route->manual_conditions) {
                $state = C::Manual;
                $reason = $route->manual_conditions ? 'Verify the additional compulsory route condition: '.$route->manual_conditions : 'The result is missing, unsupported, or uses an incompatible scale. No automatic conversion is applied.';
            }
            $outcomes[] = array_replace($base, ['status' => $state, 'student' => $student, 'result' => $comparable ? (float) $record->result_value : null, 'explanation' => $reason]);
        }
        $passed = collect($outcomes)->filter(fn ($r) => $r['status'] === C::Met)->sortByDesc('result');

        return $passed->first() ?? collect($outcomes)->firstWhere('status', C::Manual) ?? $outcomes[0];
    }

    private function income(?StudentProfile $profile, Scholarship $s, array $rule, Collection $categories): CriterionResult
    {
        $value = $profile?->household_income;
        $state = C::Manual;
        $required = 'Income limits not configured';
        if (($rule['kind'] ?? '') === 'numeric') {
            $minimum = $rule['minimum'] ?? null;
            $maximum = $rule['maximum'] ?? null;
            $required = ($minimum === null ? 'No lower bound' : 'At least RM '.number_format((float) $minimum, 2)).'; '.($maximum === null ? 'No upper bound' : 'At most RM '.number_format((float) $maximum, 2));
            if ($value !== null && ($minimum !== null || $maximum !== null)) {
                $state = ($minimum !== null && (float) $value < (float) $minimum) || ($maximum !== null && (float) $value > (float) $maximum) ? C::NotMet : C::Met;
            }
        } elseif (($rule['kind'] ?? '') === 'categories') {
            $accepted = $s->incomeCategories;
            $required = $accepted->pluck('name')->implode(', ').' (reference '.($rule['reference_year'] ?? 'unknown').')';
            $category = $this->incomeResolver->resolve($value === null ? null : (float) $value, $categories, $rule['reference_year'] ?? null);
            if ($category && $accepted->isNotEmpty() && isset($rule['reference_year']) && $accepted->every(fn ($c) => $c->is_active && $c->reference_year === (int) $rule['reference_year'])) {
                $state = $accepted->contains('id', $category->id) ? C::Met : C::NotMet;
            }
        }

        return new CriterionResult('income', 'Household income', $state, $required, $value === null ? 'Missing' : 'RM '.number_format((float) $value, 2), $state === C::Manual ? 'Income is missing or the applicable reference configuration cannot be resolved.' : 'Your monthly household income '.($state === C::Met ? 'satisfies' : 'does not satisfy').' the recorded income condition.', $state === C::Manual ? 'Add monthly household income in My Profile or confirm the reference configuration with the provider.' : null);
    }
}
