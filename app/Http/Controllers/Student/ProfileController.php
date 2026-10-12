<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileRequest;
use App\Models\IncomeCategory;
use App\Models\StudentProfile;
use App\Models\StudyField;
use App\Services\IncomeCategoryResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $profile = $request->user()->profile()->with(['academicRecords', 'studyField'])->first() ?? new StudentProfile;

        return view('student.profile', ['profile' => $profile, 'fields' => StudyField::where('is_active', true)->orWhere('id', $profile->study_field_id)->orderBy('name')->get(), 'incomeCategory' => (new IncomeCategoryResolver)->resolve($profile->household_income === null ? null : (float) $profile->household_income, IncomeCategory::all())]);
    }

    public function update(ProfileRequest $request): RedirectResponse
    {
        $data = $request->validated();
        DB::transaction(function () use ($request, $data) {
            $profile = $request->user()->profile()->firstOrCreate();
            $request->user()->setRelation('profile', $profile);
            $profile->update(collect($data)->except('academic_records')->all() + ['academic_records_complete' => false, 'study_field_undecided' => false]);
            if (array_key_exists('academic_records', $data)) {
                $retained = [];
                foreach ($data['academic_records'] as $record) {
                    $id = $record['id'] ?? null;
                    unset($record['id']);
                    $saved = $id ? $profile->academicRecords()->findOrFail($id) : $profile->academicRecords()->make();
                    $saved->fill($record)->save();
                    $retained[] = $saved->id;
                }
                $profile->academicRecords()->whereNotIn('id', $retained)->delete();
            }
        });

        return back()->with('success', 'Your profile has been saved. Recommendations reflect your latest information.');
    }
}
