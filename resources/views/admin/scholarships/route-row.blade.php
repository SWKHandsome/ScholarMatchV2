@php
    $resultType = $academicRoute['result_type'] ?? null;
    $resultLabel = match ($resultType) {
        'cgpa' => 'Minimum CGPA (out of 4.00)',
        'as_count' => 'Minimum number of As',
        default => 'Minimum result',
    };
@endphp
<fieldset class="route-row border rounded p-3 mb-3" data-academic-result>
    <legend class="float-none w-auto fs-6 px-2">Alternative academic route</legend>
    <div class="row">
        @foreach(['qualification'=>'Qualification','completion_status'=>'Required completion','result_type'=>'Result type'] as $key=>$label)
        <div class="col-md-4"><x-select :name="'academic_routes['.$index.']['.$key.']'" :label="$label" :options="config('scholarmatch.'.$key)" :value="$academicRoute[$key]??null" required /></div>
        @endforeach
        <div class="col-md-8"><x-input :name="'academic_routes['.$index.'][minimum]'" :label="$resultLabel" type="number" min="0" :max="$resultType==='cgpa'?4:($resultType==='as_count'?30:null)" :step="$resultType==='as_count'?1:'0.0001'" :value="$academicRoute['minimum']??null" data-result-value /></div>
        <div class="col-md-4 d-flex align-items-center"><button type="button" class="btn btn-outline remove-row">Remove route</button></div>
    </div>
    @if(filled($academicRoute['manual_conditions']??null))
    <input type="hidden" name="academic_routes[{{ $index }}][manual_conditions]" value="{{ $academicRoute['manual_conditions'] }}">
    <p class="help mt-2">Saved condition for this route: {{ $academicRoute['manual_conditions'] }}</p>
    @endif
</fieldset>
