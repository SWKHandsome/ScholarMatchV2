<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class IncomeConfigurationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reference_year' => ['required', 'integer', 'between:2000,2100'],
            'source_url' => ['required', 'url:http,https', 'max:2000'],
            'categories' => ['required', 'array', 'min:1', 'max:20'],
            'categories.*.name' => ['required', 'string', 'max:50', 'distinct'],
            'categories.*.minimum' => ['required', 'numeric', 'min:0', 'max:9999999999', 'decimal:0,2'],
            'categories.*.maximum' => ['nullable', 'numeric', 'min:0', 'max:9999999999', 'decimal:0,2'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $ranges = collect($this->input('categories'))->sortBy('minimum')->values();
            $expected = 0;
            foreach ($ranges as $index => $range) {
                $minimum = (int) round((float) $range['minimum'] * 100);
                $maximum = isset($range['maximum']) ? (int) round((float) $range['maximum'] * 100) : null;
                if ($minimum !== $expected || ($maximum !== null && $maximum < $minimum) || ($index < $ranges->count() - 1 && $maximum === null) || ($index === $ranges->count() - 1 && $maximum !== null)) {
                    $validator->errors()->add('categories', 'Ranges must start at RM 0, meet without gaps or overlaps to the cent, and end with an unlimited range.');

                    return;
                }
                $expected = ($maximum ?? 0) + 1;
            }
        }];
    }
}
