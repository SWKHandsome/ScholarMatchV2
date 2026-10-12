@extends('layouts.app')
@section('title','Saved Scholarships')
@section('content')
<span class="eyebrow">YOUR OPPORTUNITY LIST</span><h1>Saved scholarships</h1><p class="muted">{{ $rows->count() }} saved opportunities · Eligibility reflects your current profile. Closed opportunities remain here.</p><div class="saved-grid">@forelse($rows as $row)<x-scholarship-card :row="$row" />@empty<div class="panel empty-state"><span>♧</span><h2>A little space for your next chapter</h2><p class="muted">Save scholarships to keep the ones that matter in easy reach.</p><a class="btn btn-primary" href="{{ route('recommendations') }}">Explore recommendations →</a></div>@endforelse</div>
@endsection
