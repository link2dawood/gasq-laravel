@props(['label' => null, 'name', 'id' => null])
@php
    $inputId = $id ?? $name;
@endphp
<div class="mb-3">
    @if($label)
        <label for="{{ $inputId }}" class="form-label">{{ $label }}</label>
    @endif
    <select id="{{ $inputId }}"
            name="{{ $name }}"
            {{ $attributes->merge(['class' => 'form-select' . ($errors->has($name) ? ' is-invalid' : '')]) }}>
        {{ $slot }}
    </select>
    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
