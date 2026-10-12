@extends('layouts.app')
@section('title','Recommendations')
@section('content')
<div class="page-heading"><span class="eyebrow">FUND YOUR NEXT CHAPTER</span><h1>Scholarship recommendations</h1><p class="muted">Explore opportunities and understand how your information meets their requirements.</p></div>
<div class="recommendation-layout"><aside class="panel filter-panel"><div class="d-flex justify-content-between"><h2>Refine your search</h2><a href="{{ route('recommendations') }}">Clear</a></div><form method="GET" action="{{ route('recommendations') }}">
<x-input name="search" label="Search scholarships" :value="request('search')" placeholder="Name or provider" />
<x-select name="eligibility" label="Eligibility" :options="array_column(\App\Enums\EligibilityStatus::cases(),'value')" :value="request('eligibility')" placeholder="All decisions" />
<x-select name="field" label="Study field" :options="$fields" :mapped="true" :value="request('field')" placeholder="All fields" />
<x-select name="provider" label="Provider" :options="$providers" :value="request('provider')" placeholder="All providers" />
<x-select name="funding_level" label="Funding level" :options="config('scholarmatch.funding_level')" :value="request('funding_level')" placeholder="All levels" />
<x-select name="institution_type" label="Institution type" :options="array_diff(config('scholarmatch.institution_type'),['undecided'])" :value="request('institution_type')" placeholder="All institutions" />
<x-select name="status" label="Application status" :options="['open'=>'Open','closed'=>'Closed','all'=>'Open & closed']" :mapped="true" :value="request('status','open')" :placeholder="null" />
<x-select name="saved" label="Saved status" :options="['yes'=>'Saved only','no'=>'Not saved']" :mapped="true" :value="request('saved')" placeholder="All opportunities" />
<x-select name="sort" label="Sort by" :options="['academic'=>'Academic margin: highest first','deadline'=>'Nearest deadline','name'=>'Scholarship name','recent'=>'Recently added']" :mapped="true" :value="request('sort','academic')" :placeholder="null" />
<button class="btn btn-primary w-100">Apply filters</button></form></aside><section aria-label="Scholarship results"><div class="results-toolbar"><b>{{ $results->total() }} opportunities</b><span class="muted">Based on your current profile</span></div><div class="ranking-note"><b>Academic margin, explained</b><p>Eligible opportunities appear first. A margin measures proportional performance above an academic minimum; it does not predict an award. <a href="{{ route('profile.edit') }}">Update your profile →</a></p></div>@if(count(array_diff_key($filters,['page'=>true,'sort'=>true])))<div class="active-filters">@foreach($filters as $key=>$value)@if(!in_array($key,['page','sort']))<span>{{ ucfirst(str_replace('_',' ',$key)) }}: {{ $value }}</span>@endif @endforeach</div>@endif
@forelse($results as $row)<x-scholarship-card :row="$row" />@empty<div class="panel empty-state"><span>◇</span><h2>No opportunities found</h2><p class="muted">Try broadening your filters or come back as new scholarships are added.</p><a class="btn btn-outline" href="{{ route('recommendations') }}">Clear filters</a></div>@endforelse
<div class="mt-4">{{ $results->links() }}</div></section></div>
@endsection
