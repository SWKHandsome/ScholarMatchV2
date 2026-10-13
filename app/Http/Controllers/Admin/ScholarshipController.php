<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ScholarshipRequest;
use App\Models\IncomeCategory;
use App\Models\Scholarship;
use App\Models\StudyField;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ScholarshipController extends Controller
{
    public function index(Request $request): View
    {
        $query = Scholarship::query();
        if ($request->filled('search')) {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%')->orWhere('provider', 'like', '%'.$request->string('search').'%'));
        }
        foreach (['provider', 'source_status'] as $key) {
            if ($request->filled($key)) {
                $query->where($key, $request->string($key));
            }
        }
        $items = $query->orderBy('deadline')->orderBy('id')->get();
        if ($request->filled('status')) {
            $items = $items->filter(fn ($s) => $s->effectiveStatus() === $request->input('status'));
        }

        return view('admin.scholarships.index', ['scholarships' => $items, 'providers' => Scholarship::distinct()->orderBy('provider')->pluck('provider')]);
    }

    private function form(Scholarship $s): View
    {
        $s->load(['requirements', 'academicRoutes', 'studyFields', 'incomeCategories']);

        $incomeRule = $s->requirements?->criteria['income'] ?? [];
        $incomeReferenceYear = ($incomeRule['mode'] ?? '') === 'restricted' && ($incomeRule['kind'] ?? '') === 'categories' ? ($incomeRule['reference_year'] ?? null) : null;
        $incomeReferenceYear ??= IncomeCategory::where('is_active', true)->max('reference_year');

        return view('admin.scholarships.form', ['scholarship' => $s, 'fields' => StudyField::where('is_active', true)->orWhereIn('id', $s->studyFields->pluck('id'))->orderBy('name')->get(), 'categories' => IncomeCategory::where('is_active', true)->where('reference_year', $incomeReferenceYear)->orderBy('minimum')->get(), 'incomeReferenceYear' => $incomeReferenceYear]);
    }

    public function create(): View
    {
        Gate::authorize('create', Scholarship::class);

        return $this->form(new Scholarship);
    }

    public function edit(Scholarship $scholarship): View
    {
        Gate::authorize('update', $scholarship);

        return $this->form($scholarship);
    }

    public function show(Scholarship $scholarship): View
    {
        return view('admin.scholarships.show', ['scholarship' => $scholarship->load(['requirements', 'academicRoutes', 'studyFields', 'incomeCategories'])]);
    }

    private function persist(ScholarshipRequest $request, Scholarship $s): void
    {
        $data = $request->validated();
        DB::transaction(function () use ($request, $s, $data) {
            $basic = collect($data)->except(['criteria', 'academic_routes', 'scholarship_url', 'income_requirement', 'automatic_criteria', 'preserve_income_rule', 'manual_requirements'])->all();
            $basic['description'] = $basic['description'] ?? '';
            $basic['benefits'] = $request->boolean('automatic_criteria') ? '' : ($basic['benefits'] ?? '');
            if (array_key_exists('closing_at', $basic)) {
                $basic['closing_at'] = empty($basic['closing_at']) ? null : CarbonImmutable::parse($basic['closing_at'], 'Asia/Kuala_Lumpur')->utc()->format('Y-m-d H:i:s');
            } elseif ($s->exists && $basic['deadline'] !== $s->deadline->format('Y-m-d')) {
                $basic['closing_at'] = null;
            }
            $basic['is_fictional'] = $request->boolean('is_fictional');
            $basic['verified_by'] = $basic['source_status'] === 'reviewed' ? $request->user()->id : null;
            $basic['last_verified_at'] = $basic['source_status'] === 'reviewed' ? now() : null;
            $s->fill($basic)->save();
            $criteria = [];
            foreach (array_keys(config('scholarmatch.criteria')) as $key) {
                $rule = $data['criteria'][$key] ?? [];
                $criteria[$key] = ($rule['mode'] ?? null) === 'none' ? ['mode' => 'none'] : $rule;
            }
            if (isset($criteria['income']['reference_year'])) {
                $criteria['income']['reference_year'] = (int) $criteria['income']['reference_year'];
            }
            $s->requirements()->updateOrCreate([], ['criteria' => $criteria]);
            $s->academicRoutes()->delete();
            if (($criteria['academic']['mode'] ?? '') === 'restricted') {
                $s->academicRoutes()->createMany($data['academic_routes']);
            }
            $s->studyFields()->sync(($criteria['study_field']['mode'] ?? '') === 'restricted' ? ($criteria['study_field']['values'] ?? []) : []);
            $s->incomeCategories()->sync(($criteria['income']['mode'] ?? '') === 'restricted' && ($criteria['income']['kind'] ?? '') === 'categories' ? ($criteria['income']['category_ids'] ?? []) : []);
        });
    }

    public function store(ScholarshipRequest $request): RedirectResponse
    {
        Gate::authorize('create', Scholarship::class);
        $this->persist($request, new Scholarship);

        return redirect()->route('admin.scholarships.index')->with('success', 'Scholarship saved.');
    }

    public function update(ScholarshipRequest $request, Scholarship $scholarship): RedirectResponse
    {
        Gate::authorize('update', $scholarship);
        $this->persist($request, $scholarship);

        return redirect()->route('admin.scholarships.index')->with('success', 'Scholarship updated.');
    }

    public function deactivate(Scholarship $scholarship): RedirectResponse
    {
        Gate::authorize('update', $scholarship);
        $scholarship->update(['status' => 'inactive']);

        return back()->with('success', 'Scholarship deactivated. Bookmarks are preserved.');
    }

    public function destroy(Scholarship $scholarship): RedirectResponse
    {
        Gate::authorize('delete', $scholarship);
        $scholarship->delete();

        return redirect()->route('admin.scholarships.index')->with('success', 'Scholarship deleted.');
    }
}
