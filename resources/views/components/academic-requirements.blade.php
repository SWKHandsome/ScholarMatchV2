@props(['evaluation'])

<div class="academic-pathways" role="group" aria-label="Academic requirements">
    <div class="pathways-heading">
        <span>Academic pathways</span>
        @if(count($evaluation->academicRoutes) > 1)
            <small>Meet any one pathway</small>
        @endif
    </div>

    @if($evaluation->academicRoutes)
        <ul class="pathways-list">
            @foreach($evaluation->academicRoutes as $route)
                @unless($loop->first)
                    <li class="pathway-divider" aria-hidden="true"><span>OR</span></li>
                @endunless
                <li class="academic-pathway">
                    <span class="pathway-qualification">{{ $route['qualification'] }}</span>
                    <div class="pathway-description">
                        <strong>{{ $route['result_requirement'] }}</strong>
                        <span>{{ $route['completion_requirement'] }}</span>
                        @if($route['manual_conditions'])
                            <p class="pathway-condition"><b>Also verify</b> {{ $route['manual_conditions'] }}</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @else
        <p class="pathway-fallback">{{ $evaluation->criteria['academic']->status === \App\Enums\CriterionStatus::NotApplicable ? 'No academic restriction' : $evaluation->criteria['academic']->requirement }}</p>
    @endif
</div>
