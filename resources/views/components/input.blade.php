@props(['label' => null, 'name', 'type' => 'text', 'id' => null])
@php
    $inputId = $id ?? $name;
@endphp
<div class="mb-3">
    @if($label)
        <label for="{{ $inputId }}" class="form-label">{{ $label }}</label>
    @endif
    <input type="{{ $type }}"
           id="{{ $inputId }}"
           name="{{ $name }}"
           {{ $attributes->merge(['class' => 'form-control' . ($errors->has($name) ? ' is-invalid' : '')]) }}>
    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
