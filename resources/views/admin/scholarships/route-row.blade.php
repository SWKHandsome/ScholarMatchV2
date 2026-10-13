@php
    $qualification = $academicRoute['qualification'] ?? null;
    $expectedResultType = is_string($qualification) ? config('scholarmatch.qualification_result_types.'.$qualification) : null;
    $resultType = isset($academicRoute['id']) ? ($academicRoute['result_type'] ?? $expectedResultType) : $expectedResultType;
    $resultLabel = match ($resultType) {
        'cgpa' => 'Minimum CGPA (out of 4.00)',
        'as_count' => 'Minimum number of As',
        default => 'Minimum result',
    };
@endphp
<fieldset class="route-row border rounded p-3 mb-3" data-academic-result>
    <legend class="float-none w-auto fs-6 px-2">Alternative academic route</legend>
    @if(isset($academicRoute['id']))<input type="hidden" name="academic_routes[{{ $index }}][id]" value="{{ $academicRoute['id'] }}">@endif
    <input type="hidden" name="academic_routes[{{ $index }}][result_type]" value="{{ $resultType }}">
    <div class="row">
        <div class="col-md-6"><x-select :name="'academic_routes['.$index.'][qualification]'" label="Qualification" :options="config('scholarmatch.qualification')" :result-types="config('scholarmatch.qualification_result_types')" :value="$qualification" placeholder="Choose qualification" required /></div>
        <div class="col-md-6" data-result-input @if(!in_array($resultType,['cgpa','as_count'])) hidden @endif><x-input :name="'academic_routes['.$index.'][minimum]'" :label="$resultLabel" type="number" min="0" :max="$resultType==='cgpa'?4:($resultType==='as_count'?30:null)" :step="$resultType==='as_count'?1:'0.0001'" :value="$academicRoute['minimum']??null" data-result-value /></div>
        <div class="col-12"><p class="help" data-academic-help>@if($resultType==='other') Add the result requirement to Special requirements below for manual verification. @elseif($resultType) Result format is selected automatically from the qualification. @else Choose a qualification to see the minimum result input. @endif</p></div>
        @if($resultType && $resultType!==$expectedResultType)<div class="col-12"><p class="help" data-saved-result-format>Saved result format: {{ config('scholarmatch.labels.'.$resultType,$resultType) }}. It is kept until the qualification changes.</p></div>@endif
        <div class="col-md-4 d-flex align-items-center"><button type="button" class="btn btn-outline remove-row">Remove route</button></div>
    </div>
    @if(filled($academicRoute['manual_conditions']??null))
    <input type="hidden" name="academic_routes[{{ $index }}][manual_conditions]" value="{{ $academicRoute['manual_conditions'] }}">
    <p class="help mt-2">Saved condition for this route: {{ $academicRoute['manual_conditions'] }}</p>
    @endif
</fieldset>
