<?php

namespace App\Http\Requests;

use App\Models\IncomeCategory;
use App\Models\StudyField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ScholarshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    protected function prepareForValidation(): void
    {
        if (! $this->input('academic_routes')) {
            $this->merge(['academic_routes' => []]);
        }
    }

    public function rules(): array
    {
        $published = $this->input('status') !== 'inactive';
        $rules = [
            'name' => ['required', 'string', 'max:200'], 'provider' => ['required', 'string', 'max:150'], 'description' => ['required', 'string', 'max:10000'], 'benefits' => ['required', 'string', 'max:5000'],
            'deadline' => ['required', 'date_format:Y-m-d'], 'closing_at' => ['nullable', 'date_format:Y-m-d\TH:i'], 'application_url' => ['required', 'url:http,https', 'max:2000'], 'source_url' => ['required', 'url:http,https', 'max:2000'],
            'status' => ['required', Rule::in(['open', 'closed', 'inactive'])], 'source_status' => ['required', Rule::in($published ? ['reviewed'] : ['reviewed', 'unreviewed'])], 'is_fictional' => ['sometimes', 'boolean'], 'criteria' => ['required', 'array'],
            'academic_routes' => ['array', 'max:20'], 'academic_routes.*' => ['array'], 'criteria.*' => ['array'], 'academic_routes.*.qualification' => ['required', Rule::in(config('scholarmatch.qualification'))],
            'academic_routes.*.completion_status' => ['required', Rule::in(config('scholarmatch.completion_status'))], 'academic_routes.*.result_type' => ['required', Rule::in(config('scholarmatch.result_type'))],
            'academic_routes.*.minimum' => ['nullable', 'numeric', 'min:0', 'max:9999', 'decimal:0,4'], 'academic_routes.*.grading_scale' => ['nullable', 'numeric', 'gt:0', 'max:30', 'decimal:0,4'], 'academic_routes.*.manual_conditions' => ['nullable', 'string', 'max:2000'],
            'criteria.income.kind' => ['nullable', Rule::in(['numeric', 'categories'])], 'criteria.income.minimum' => ['nullable', 'numeric', 'min:0', 'decimal:0,2', 'max:9999999999'], 'criteria.income.maximum' => ['nullable', 'numeric', 'min:0', 'decimal:0,2', 'max:9999999999'],
            'criteria.income.reference_year' => ['nullable', 'integer', 'between:2000,2100'], 'criteria.income.category_ids' => ['nullable', 'array'], 'criteria.income.category_ids.*' => ['integer', 'exists:income_categories,id', 'distinct'],
            'criteria.study_field.values' => ['nullable', 'array'], 'criteria.study_field.values.*' => ['integer', 'exists:study_fields,id', 'distinct'],
        ];
        foreach (array_keys(config('scholarmatch.criteria')) as $key) {
            $rules["criteria.$key.mode"] = [$published ? 'required' : 'nullable', Rule::in($key === 'additional' ? ['none', 'manual'] : ['none', 'restricted', 'manual'])];
            $rules["criteria.$key.notes"] = ['nullable', 'string', 'max:2000'];
            if (in_array($key, ['citizenship', 'funding_level', 'enrolment_status', 'institution_type', 'study_location', 'residence_state'])) {
                $rules["criteria.$key.values"] = ['nullable', 'array'];
                $rules["criteria.$key.values.*"] = [Rule::in(array_diff(config('scholarmatch.'.$key), ['undecided'])), 'distinct'];
            }
        }

        return $rules;
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $criteria = $this->input('criteria', []);
            if (! is_array($criteria) || ! is_array($this->input('academic_routes', []))) {
                return;
            }
            foreach (array_keys(config('scholarmatch.criteria')) as $key) {
                $rule = $criteria[$key] ?? [];
                if (! is_array($rule)) {
                    continue;
                }
                if (($rule['mode'] ?? '') === 'manual' && empty($rule['notes'])) {
                    $validator->errors()->add("criteria.$key.notes", 'Describe the condition to verify with the provider.');
                }
                if (($rule['mode'] ?? '') !== 'restricted') {
                    continue;
                }
                if ($key === 'academic' && ! $this->input('academic_routes')) {
                    $validator->errors()->add('academic_routes', 'Add at least one academic route.');
                } elseif (! in_array($key, ['academic', 'income']) && empty($rule['values'])) {
                    $validator->errors()->add("criteria.$key.values", 'Select at least one accepted value.');
                }
            }
            $income = $criteria['income'] ?? [];
            if (! is_array($income) || ! is_array($criteria['study_field']['values'] ?? []) || ! is_array($income['category_ids'] ?? [])) {
                return;
            }
            if (($income['mode'] ?? '') === 'restricted') {
                if (($income['kind'] ?? '') === 'numeric') {
                    if (! isset($income['minimum']) && ! isset($income['maximum'])) {
                        $validator->errors()->add('criteria.income.maximum', 'Provide an explicit income boundary.');
                    }
                    if (isset($income['minimum'],$income['maximum']) && (float) $income['minimum'] > (float) $income['maximum']) {
                        $validator->errors()->add('criteria.income.maximum', 'Maximum must be at least the minimum.');
                    }
                } elseif (($income['kind'] ?? '') === 'categories') {
                    if (empty($income['category_ids']) || empty($income['reference_year'])) {
                        $validator->errors()->add('criteria.income.category_ids', 'Select categories and their reference year.');
                    } elseif (IncomeCategory::whereIn('id', $income['category_ids'])->where('reference_year', $income['reference_year'])->where('is_active', true)->count() !== count($income['category_ids'])) {
                        $validator->errors()->add('criteria.income.category_ids', 'Categories must be active and belong to the selected reference year.');
                    }
                } else {
                    $validator->errors()->add('criteria.income.kind', 'Choose numeric limits or income categories.');
                }
            }
            $existing = $this->route('scholarship')?->studyFields()->pluck('study_fields.id')->all() ?? [];
            foreach ($criteria['study_field']['values'] ?? [] as $id) {
                if (! in_array((int) $id, $existing) && ! StudyField::whereKey($id)->where('is_active', true)->exists()) {
                    $validator->errors()->add('criteria.study_field.values', 'Select active study fields.');
                }
            }
            foreach ($this->input('academic_routes', []) as $index => $route) {
                if (! is_array($route)) {
                    continue;
                }
                $numeric = in_array($route['result_type'] ?? '', ['cgpa', 'as_count']);
                if ($numeric && (! isset($route['minimum']) || ! isset($route['grading_scale']))) {
                    $validator->errors()->add("academic_routes.$index.minimum", 'Provide a minimum and grading scale.');
                }
                if (isset($route['minimum'],$route['grading_scale']) && (float) $route['minimum'] > (float) $route['grading_scale']) {
                    $validator->errors()->add("academic_routes.$index.minimum", 'Minimum cannot exceed the scale.');
                }
                if (($route['result_type'] ?? '') === 'as_count' && ((isset($route['minimum']) && (float) $route['minimum'] != (int) $route['minimum']) || (isset($route['grading_scale']) && (float) $route['grading_scale'] != (int) $route['grading_scale']))) {
                    $validator->errors()->add("academic_routes.$index.minimum", 'Counts of As must be whole numbers.');
                }
                if (! $numeric && empty($route['manual_conditions'])) {
                    $validator->errors()->add("academic_routes.$index.manual_conditions", 'Describe the unsupported academic requirement.');
                }
            }
            if (is_string($this->input('closing_at')) && substr($this->input('closing_at'), 0, 10) !== $this->input('deadline')) {
                $validator->errors()->add('closing_at', 'Provider closing time must fall on the deadline in Malaysia time.');
            }
        }];
    }
}
