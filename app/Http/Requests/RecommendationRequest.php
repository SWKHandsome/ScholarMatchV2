<?php

namespace App\Http\Requests;

use App\Enums\EligibilityStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecommendationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'student';
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:200'], 'provider' => ['nullable', 'string', 'max:150'], 'eligibility' => ['nullable', Rule::in(array_column(EligibilityStatus::cases(), 'value'))],
            'field' => ['nullable', 'integer', 'exists:study_fields,id'], 'funding_level' => ['nullable', Rule::in(config('scholarmatch.funding_level'))],
            'institution_type' => ['nullable', Rule::in(config('scholarmatch.institution_type'))], 'status' => ['nullable', Rule::in(['open', 'closed', 'all'])],
            'saved' => ['nullable', Rule::in(['yes', 'no'])], 'sort' => ['nullable', Rule::in(['academic', 'deadline', 'name', 'recent'])], 'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
