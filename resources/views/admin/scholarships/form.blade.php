@extends('layouts.app')
@section('title',$scholarship->exists?'Edit Scholarship':'Add Scholarship')
@section('content')
<a href="{{ route('admin.scholarships.index') }}">← Scholarships</a><h1 class="mt-3">{{ $scholarship->exists?'Edit scholarship':'Add scholarship' }}</h1><p class="muted">Set the requirements that apply. Leave an eligibility section empty for no restriction.</p>
<form method="POST" action="{{ $scholarship->exists?route('admin.scholarships.update',$scholarship):route('admin.scholarships.store') }}">@csrf @if($scholarship->exists) @method('PUT') @endif
<input type="hidden" name="automatic_criteria" value="1">
<div class="panel mb-4">
    <h2>Basic information</h2>
    <p class="help">Fill in these five details. Include the scholarship's purpose and financial support in its description.</p>
    <div class="row">
        <div class="col-md-6"><x-input name="name" label="Scholarship name *" :value="old('name',$scholarship->name)" placeholder="e.g. Future Leaders Scholarship" maxlength="200" required /></div>
        <div class="col-md-6"><x-input name="provider" label="Provider *" :value="old('provider',$scholarship->provider)" placeholder="e.g. ABC Foundation" maxlength="150" required /></div>
        <div class="col-md-6 form-field">
            <label for="description">Scholarship description *</label>
            <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="3" maxlength="10000" placeholder="e.g. Supports undergraduate students with tuition fees and a monthly allowance." required @error('description') aria-invalid="true" aria-describedby="description-error" @enderror>{{ old('description',$scholarship->fullDescription()) }}</textarea>
            @error('description')<span class="field-error" id="description-error">{{ $message }}</span>@enderror
        </div>
        <div class="col-md-6"><x-input name="deadline" label="Application deadline *" type="date" :value="old('deadline',$scholarship->deadline?->format('Y-m-d'))" required /></div>
        <div class="col-12">
            <x-input name="scholarship_url" label="Official scholarship link *" type="url" :value="old('scholarship_url',$scholarship->source_url)" placeholder="https://..." maxlength="2000" aria-describedby="scholarship-link-help" required />
            <p class="help" id="scholarship-link-help">Link to the provider's scholarship page for details and application instructions.</p>
        </div>
    </div>
</div>
@php
    $criteria = old('criteria', $scholarship->requirements?->criteria ?? []);
    $hasSubmittedSelections = old('automatic_criteria', false);
@endphp
@foreach(array_diff_key(config('scholarmatch.criteria'),['additional'=>true]) as $key=>$label)
@php
    $criterion = $criteria[$key] ?? [];
@endphp
<div class="panel mb-4 criterion-form"><h2>{{ $label }}</h2>
@if($key==='income')
@php
    $savedIncome = $scholarship->requirements?->criteria['income'] ?? [];
    $hasExistingIncomeRule = ($savedIncome['mode'] ?? '') === 'restricted' && ($savedIncome['kind'] ?? '') === 'numeric';
    $selectedCategories = $hasSubmittedSelections ? ($criterion['category_ids'] ?? []) : (($criterion['mode'] ?? '') === 'restricted' && ($criterion['kind'] ?? '') === 'categories' ? ($criterion['category_ids'] ?? $scholarship->incomeCategories->pluck('id')->all()) : []);
    $selectedCategories = is_array($selectedCategories) ? $selectedCategories : [];
@endphp
<div data-income-form>
    <p class="help mb-3">Add the income categories that can apply. Leave the selection empty for no income restriction.</p>
    <fieldset data-income-categories>
        <legend class="visually-hidden">Eligible income categories</legend>
        <x-selection-dropdown name="criteria[income][category_ids]" label="Income categories" :options="$categories->pluck('name','id')->all()" :values="$selectedCategories" />
        @if($categories->isNotEmpty())
        <p class="help mt-2">Using configured categories for {{ $incomeReferenceYear }}. Select one or more; income ranges are filled automatically.</p>
        @else
        <p class="help">No active categories are available for this reference year. <a href="{{ route('admin.income.index') }}">Configure income categories</a> to add an income restriction.</p>
        @endif
    </fieldset>
    @if($hasExistingIncomeRule)
    <div class="mt-3">
        <x-select name="preserve_income_rule" label="Saved income requirement" :options="[1=>'Keep saved income requirement',0=>'Remove saved income requirement']" :mapped="true" :value="old('preserve_income_rule',1)" :placeholder="null" data-preserve-income />
        <p class="help">
            @if(isset($savedIncome['minimum'])) At least RM {{ number_format((float)$savedIncome['minimum'],2) }} per month. @endif
            @if(isset($savedIncome['maximum'])) At most RM {{ number_format((float)$savedIncome['maximum'],2) }} per month. @endif
        </p>
        <p class="help">Adding categories replaces this saved limit. Choose Remove saved income requirement to clear it.</p>
    </div>
    @endif
</div>
@else
@php
    $selectedValues = $hasSubmittedSelections ? ($criterion['values'] ?? []) : (($criterion['mode'] ?? '') === 'restricted' ? ($criterion['values'] ?? ($key === 'study_field' ? $scholarship->studyFields->pluck('id')->all() : [])) : []);
    $selectedValues = is_array($selectedValues) ? $selectedValues : [];
@endphp
@if($key==='academic')<p class="help">Add an accepted qualification to set an academic requirement. No routes means no academic restriction. Meeting one complete route is enough.</p><input type="hidden" name="academic_routes" value=""><div id="route-rows">@foreach(old('academic_routes',($criterion['mode']??'')==='restricted'?$scholarship->academicRoutes->toArray():[])?:[] as $index=>$academicRoute)@include('admin.scholarships.route-row')@endforeach</div><button type="button" class="btn btn-outline" data-add-row="route">+ Add academic route</button>
@elseif($key==='study_field')
<x-selection-dropdown name="criteria[study_field][values]" label="Accepted study fields" :options="$fields->pluck('name','id')->all()" :values="$selectedValues" />
@else
@php
    $options = collect(array_diff(config('scholarmatch.'.$key), ['undecided']))->mapWithKeys(fn ($option) => [$option => config('scholarmatch.labels.'.$option, ucfirst(str_replace('_', ' ', $option)))])->all();
    $allowsMultiple = ! in_array($key, ['citizenship', 'study_location']) || count($selectedValues) > 1;
@endphp
@if($key==='residence_state')<p class="help">Choose from Malaysia's 13 states and 3 federal territories.</p>@endif
@if(in_array($key,['citizenship','study_location']) && $allowsMultiple)<p class="help">This scholarship has multiple saved choices. They are kept below; remove any that no longer apply.</p>@endif
<x-selection-dropdown :name="'criteria['.$key.'][values]'" :label="$label" :options="$options" :values="$selectedValues" :multiple="$allowsMultiple" />
@endif
@endif
</div>
@endforeach
<div class="panel mb-4">
    <details class="scholarship-details" @if(filled(old('manual_requirements',$scholarship->manualVerificationNotes()))||$errors->has('manual_requirements')) open @endif>
        <summary>Special requirements (manual verification, optional)</summary>
        <p class="help mt-2">Add conditions the app cannot check, such as leadership experience, an interview, or specific subject grades. Students will see an alert to verify them with the provider.</p>
        <div class="form-field">
            <label for="manual_requirements">Requirements to verify with the provider</label>
            <textarea id="manual_requirements" name="manual_requirements" class="form-control @error('manual_requirements') is-invalid @enderror" rows="4" maxlength="10000" placeholder="e.g. Applicants must show leadership experience and pass an interview." @error('manual_requirements') aria-invalid="true" aria-describedby="manual-requirements-error" @enderror>{{ old('manual_requirements',$scholarship->manualVerificationNotes()) }}</textarea>
            @error('manual_requirements')<span class="field-error" id="manual-requirements-error">{{ $message }}</span>@enderror
        </div>
    </details>
</div>
<div class="panel mb-4"><h2>Source verification & publication</h2><p class="help">Source review confirms scholarship information, separately from student eligibility. Saving a reviewed record records your identity and the review time.</p><div class="row"><div class="col-md-6"><x-select name="source_status" label="Source verification" :options="['unreviewed','reviewed']" :value="old('source_status',$scholarship->source_status??'unreviewed')" :placeholder="null" /></div><div class="col-md-6"><x-select name="status" label="Application status" :options="['inactive','open','closed']" :value="old('status',$scholarship->status??'inactive')" :placeholder="null" /></div><div class="col-md-6"><x-select name="is_fictional" label="Record type" :options="[0=>'Real scholarship',1=>'Fictional demonstration record']" :mapped="true" :value="old('is_fictional',(int)$scholarship->is_fictional)" :placeholder="null" /><p class="help">Fictional demonstration records are clearly labelled to students.</p></div></div></div><button class="btn btn-primary">Save scholarship</button></form><template id="route-template">@include('admin.scholarships.route-row',['index'=>'__INDEX__','academicRoute'=>[]])</template>
@endsection
