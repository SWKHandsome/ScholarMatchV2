@php
    $qualification = $record['qualification'] ?? null;
    $expectedResultType = is_string($qualification) ? config('scholarmatch.qualification_result_types.'.$qualification) : null;
    $resultType = isset($record['id']) ? ($record['result_type'] ?? $expectedResultType) : $expectedResultType;
    $resultLabel = match ($resultType) {
        'cgpa' => 'CGPA (out of 4.00)',
        'as_count' => 'Number of As',
        default => 'Result value (leave empty if unknown)',
    };
@endphp
<fieldset class="academic-row rounded border p-3 mb-3" data-academic-result>
    <legend class="float-none w-auto px-2" style="font-size:14px">Qualification</legend>
    @if(isset($record['id']))<input type="hidden" name="academic_records[{{ $index }}][id]" value="{{ $record['id'] }}">@endif
    <input type="hidden" name="academic_records[{{ $index }}][result_type]" value="{{ $resultType }}">
    <div class="row">
        <div class="col-md-6"><x-select :name="'academic_records['.$index.'][qualification]'" label="Qualification" :options="config('scholarmatch.qualification')" :result-types="config('scholarmatch.qualification_result_types')" :value="$qualification" placeholder="Choose qualification" required /></div>
        <div class="col-md-6" data-result-input @if(!in_array($resultType,['cgpa','as_count'])) hidden @endif>
            <x-input :name="'academic_records['.$index.'][result_value]'" :label="$resultLabel" type="number" min="0" :max="$resultType==='cgpa'?4:($resultType==='as_count'?30:null)" :step="$resultType==='as_count'?1:'0.0001'" :value="$record['result_value']??null" data-result-value />
            <p class="help">Leave the result empty if it is unknown.</p>
        </div>
        <div class="col-12"><p class="help" data-academic-help>@if($resultType==='other') Enter the result details in the notes below. These results require manual verification. @elseif($resultType) Result format is selected automatically from the qualification. @else Choose a qualification to see the result input. @endif</p></div>
        @if($resultType && $resultType!==$expectedResultType)<div class="col-12"><p class="help" data-saved-result-format>Saved result format: {{ config('scholarmatch.labels.'.$resultType,$resultType) }}. It is kept until the qualification changes.</p></div>@endif
        <div class="col-md-6"><x-input :name="'academic_records['.$index.'][completion_year]'" label="Qualification year (optional)" type="number" :value="$record['completion_year']??null" /></div>
        <div class="col-md-9"><x-input :name="'academic_records['.$index.'][result_notes]'" label="Other result details (optional)" :value="$record['result_notes']??null" /></div>
        <div class="col-md-3 d-flex align-items-center justify-content-end"><button type="button" class="btn btn-outline remove-row">Remove qualification</button></div>
    </div>
</fieldset>
