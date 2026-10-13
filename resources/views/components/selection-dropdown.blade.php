@props(['name', 'label', 'options', 'values' => [], 'multiple' => true])
@php
    $errorKey = str_replace(['][', '[', ']'], ['.', '.', ''], $name);
    $inputId = str_replace(['[', ']'], ['-', ''], $name);
    $selectedValues = array_map('strval', is_array($values) ? $values : []);
    $messages = collect([$errors->get($errorKey), $errors->get($errorKey.'.*')])->flatten()->unique();
@endphp
<div class="form-field selection-dropdown" @if($multiple) data-selection-dropdown="{{ $name }}[]" @endif>
    <label for="{{ $inputId }}">{{ $label }} ({{ $multiple ? 'multiple choices' : 'one choice' }})</label>
    <select id="{{ $inputId }}" name="{{ $name }}[]" class="form-select {{ $messages->isNotEmpty() ? 'is-invalid' : '' }}" @if($multiple) multiple size="4" @endif aria-describedby="{{ $inputId }}-help{{ $messages->isNotEmpty() ? ' '.$inputId.'-error' : '' }}" @if($messages->isNotEmpty()) aria-invalid="true" @endif>
        <option value="" @if($multiple) disabled @else @selected(empty($selectedValues)) @endif>{{ $multiple ? 'Select an option to add' : 'No restriction' }}</option>
        @foreach($options as $value => $text)
        <option value="{{ $value }}" @selected(in_array((string) $value, $selectedValues, true))>{{ $text }}</option>
        @endforeach
    </select>
    @if($multiple)
    <ul class="selected-choices" data-selected-choices aria-label="Selected {{ strtolower($label) }}" hidden></ul>
    <p class="help selection-status" data-selection-status role="status" hidden></p>
    <p class="help" id="{{ $inputId }}-help">Choose an option to add it. You can add more than one. Remove all choices for no restriction.</p>
    <noscript><p class="help">Hold Ctrl (or Command) to select several options. Deselect all for no restriction.</p></noscript>
    @else
    <p class="help" id="{{ $inputId }}-help">Choose one option, or No restriction to accept either.</p>
    @endif
    @if($messages->isNotEmpty())
    <div id="{{ $inputId }}-error">@foreach($messages as $message)<span class="field-error">{{ $message }}</span>@endforeach</div>
    @endif
</div>
