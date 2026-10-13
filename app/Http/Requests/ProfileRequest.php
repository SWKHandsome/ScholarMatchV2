<?php

namespace App\Http\Requests;

use App\Models\StudyField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'student';
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('academic_records') && ! $this->input('academic_records')) {
            $this->merge(['academic_records' => []]);
        }

        $records = $this->input('academic_records');
        if (is_array($records)) {
            foreach ($records as $index => $record) {
                if (! is_array($record)) {
                    continue;
                }
                if (($record['result_type'] ?? '') === 'cgpa') {
                    $records[$index]['grading_scale'] = 4;
                } elseif (! array_key_exists('grading_scale', $record)) {
                    $records[$index]['grading_scale'] = null;
                }
            }
            $this->merge(['academic_records' => $records]);
        }
    }

    public function rules(): array
    {
        $rules = [];
        foreach (['citizenship', 'residence_state', 'current_education_level', 'funding_level', 'enrolment_status', 'institution_type', 'study_location'] as $key) {
            $rules[$key] = ['nullable', Rule::in(config('scholarmatch.'.$key))];
        }

        return $rules + [
            'study_field_id' => ['nullable', 'integer', 'exists:study_fields,id'],
            'study_field_undecided' => ['sometimes', 'boolean'],
            'household_income' => ['nullable', 'numeric', 'min:0', 'max:9999999999', 'decimal:0,2'],
            'academic_records_complete' => ['sometimes', 'boolean'],
            'academic_records' => ['sometimes', 'array', 'max:20'],
            'academic_records.*' => ['array'],
            'academic_records.*.id' => ['nullable', 'integer', 'distinct'],
            'academic_records.*.qualification' => ['required', Rule::in(config('scholarmatch.qualification'))],
            'academic_records.*.completion_status' => ['required', Rule::in(config('scholarmatch.completion_status'))],
            'academic_records.*.result_type' => ['required', Rule::in(config('scholarmatch.result_type'))],
            'academic_records.*.result_value' => ['nullable', 'numeric', 'min:0', 'max:9999', 'decimal:0,4'],
            'academic_records.*.grading_scale' => ['nullable', 'numeric', 'gt:0', 'max:30', 'decimal:0,4'],
            'academic_records.*.result_notes' => ['nullable', 'string', 'max:255'],
            'academic_records.*.completion_year' => ['nullable', 'integer', 'between:1950,'.now()->year],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $profile = $this->user()->profile()->first();
            if (! is_array($this->input('academic_records', [])) || $validator->errors()->has('study_field_id')) {
                return;
            }
            if ($this->input('study_field_id') && ! StudyField::whereKey($this->input('study_field_id'))->where('is_active', true)->exists() && (int) $this->input('study_field_id') !== $profile?->study_field_id) {
                $validator->errors()->add('study_field_id', 'Select an active study field.');
            }
            if ($this->boolean('study_field_undecided') && $this->input('study_field_id')) {
                $validator->errors()->add('study_field_id', 'Choose a field or mark it undecided, not both.');
            }
            foreach ($this->input('academic_records', []) as $index => $record) {
                if (! is_array($record)) {
                    continue;
                }
                if (isset($record['id']) && ! $profile?->academicRecords()->whereKey($record['id'])->exists()) {
                    $validator->errors()->add("academic_records.$index.id", 'This qualification does not belong to your profile.');
                }
                if (in_array($record['result_type'] ?? '', ['as_count', 'cgpa'])) {
                    $maximum = $record['grading_scale'] ?? 30;
                    if (isset($record['result_value']) && (float) $record['result_value'] > (float) $maximum) {
                        $validator->errors()->add("academic_records.$index.result_value", $record['result_type'] === 'cgpa' ? 'CGPA cannot exceed 4.00.' : 'The number of As cannot exceed '.$maximum.'.');
                    }
                    if (($record['result_type'] ?? '') === 'as_count' && ((isset($record['result_value']) && (float) $record['result_value'] != (int) $record['result_value']) || (isset($record['grading_scale']) && (float) $record['grading_scale'] != (int) $record['grading_scale']))) {
                        $validator->errors()->add("academic_records.$index.result_value", 'Counts of As must be whole numbers.');
                    }
                }
            }
        }];
    }
}
