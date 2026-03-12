@props(['variant' => 'primary', 'size' => null])
@php
    $class = 'btn btn-' . $variant;
    if ($size) $class .= ' btn-' . $size;
@endphp
<button {{ $attributes->merge(['type' => 'button', 'class' => $class]) }}>
    {{ $slot }}
</button>
