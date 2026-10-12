<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecommendationRequest;
use App\Models\IncomeCategory;
use App\Models\Scholarship;
use App\Models\StudyField;
use App\Services\RecommendationService;
use App\Services\ScholarshipEligibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ScholarshipController extends Controller
{
    public function index(RecommendationRequest $request, RecommendationService $service): View
    {
        $filters = array_filter($request->validated(), fn ($v) => $v !== null && $v !== '');
        $rows = $service->order($service->filter($service->forUser($request->user()), $filters), $filters['sort'] ?? 'academic');

        return view('student.recommendations', ['results' => $service->paginate($rows, (int) ($filters['page'] ?? 1)), 'fields' => StudyField::where('is_active', true)->orderBy('name')->pluck('name', 'id'), 'providers' => Scholarship::where('status', '!=', 'inactive')->distinct()->orderBy('provider')->pluck('provider'), 'filters' => $filters]);
    }

    public function show(Request $request, Scholarship $scholarship, ScholarshipEligibilityService $service): View
    {
        Gate::authorize('view', $scholarship);
        $scholarship->load(['requirements', 'academicRoutes', 'studyFields', 'incomeCategories']);
        $profile = $request->user()->profile()->with(['academicRecords', 'studyField'])->first();

        return view('student.details', ['scholarship' => $scholarship, 'evaluation' => $service->evaluate($profile, $scholarship, IncomeCategory::all()), 'saved' => $request->user()->savedScholarships()->where('scholarship_id', $scholarship->id)->exists()]);
    }

    public function saved(Request $request, RecommendationService $service): View
    {
        return view('student.saved', ['rows' => $service->order($service->forUser($request->user(), true), 'deadline')]);
    }
}
