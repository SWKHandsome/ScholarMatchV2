<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\IncomeConfigurationRequest;
use App\Models\IncomeCategory;
use App\Models\StudyField;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReferenceController extends Controller
{
    public function fields(): View
    {
        return view('admin.fields', ['fields' => StudyField::orderBy('name')->get()]);
    }

    public function storeField(Request $request): RedirectResponse
    {
        StudyField::create($request->validate(['name' => ['required', 'string', 'max:100', 'unique:study_fields']]));

        return back()->with('success', 'Study field added.');
    }

    public function updateField(Request $request, StudyField $field): RedirectResponse
    {
        $field->update($request->validate(['name' => ['required', 'string', 'max:100', Rule::unique('study_fields')->ignore($field->id)], 'is_active' => ['required', 'boolean']]));

        return back()->with('success', 'Study field updated. Historical references are preserved.');
    }

    public function income(): View
    {
        $year = IncomeCategory::where('is_active', true)->max('reference_year') ?? now()->year;

        return view('admin.income', ['categories' => IncomeCategory::where('reference_year', $year)->where('is_active', true)->orderBy('minimum')->get(), 'year' => $year]);
    }

    public function updateIncome(IncomeConfigurationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        DB::transaction(function () use ($data) {
            IncomeCategory::where('reference_year', $data['reference_year'])->update(['is_active' => false]);
            foreach ($data['categories'] as $category) {
                IncomeCategory::updateOrCreate(['name' => $category['name'], 'reference_year' => $data['reference_year']], ['minimum' => $category['minimum'], 'maximum' => $category['maximum'] ?? null, 'source_url' => $data['source_url'], 'is_active' => true]);
            }
        });

        return back()->with('success', 'Income configuration updated. Subsequent evaluations use the current thresholds.');
    }
}
