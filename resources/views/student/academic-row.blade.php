@php
    $resultType = $record['result_type'] ?? null;
    $resultLabel = match ($resultType) {
        'cgpa' => 'CGPA (out of 4.00)',
        'as_count' => 'Number of As',
        default => 'Result value (leave empty if unknown)',
    };
@endphp
<fieldset class="academic-row rounded border p-3 mb-3" data-academic-result>
    <legend class="float-none w-auto px-2" style="font-size:14px">Qualification</legend>
    @if(isset($record['id']))<input type="hidden" name="academic_records[{{ $index }}][id]" value="{{ $record['id'] }}">@endif
    <div class="row">
        @foreach(['qualification'=>'Qualification','completion_status'=>'Completion status','result_type'=>'Result type'] as $key=>$label)
        <div class="col-md-4"><x-select :name="'academic_records['.$index.']['.$key.']'" :label="$label" :options="config('scholarmatch.'.$key)" :value="$record[$key]??null" required /></div>
        @endforeach
        <div class="col-md-6">
            <x-input :name="'academic_records['.$index.'][result_value]'" :label="$resultLabel" type="number" min="0" :max="$resultType==='cgpa'?4:($resultType==='as_count'?30:null)" :step="$resultType==='as_count'?1:'0.0001'" :value="$record['result_value']??null" data-result-value />
            <p class="help">Leave the result empty if it is unknown.</p>
        </div>
        <div class="col-md-6"><x-input :name="'academic_records['.$index.'][completion_year]'" label="Completion year (optional)" type="number" :value="$record['completion_year']??null" /></div>
        <div class="col-md-9"><x-input :name="'academic_records['.$index.'][result_notes]'" label="Other result details (optional)" :value="$record['result_notes']??null" /></div>
        <div class="col-md-3 d-flex align-items-center justify-content-end"><button type="button" class="btn btn-outline remove-row">Remove qualification</button></div>
    </div>
</fieldset>
