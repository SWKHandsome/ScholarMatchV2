@props(['name', 'label', 'type' => 'text', 'value' => null])
@php($errorKey = str_replace(['][', '[', ']'], ['.', '.', ''], $name))
<div class="form-field"><label for="{{ $name }}">{{ $label }}</label><input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ $type === 'password' ? '' : $value }}" {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($errorKey)]) }} @error($errorKey) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror>@error($errorKey)<span class="field-error" id="{{ $name }}-error">{{ $message }}</span>@enderror</div>
