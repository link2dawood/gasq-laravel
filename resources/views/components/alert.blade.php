@props(['type' => 'info', 'dismissible' => false])
@php
    $classes = match($type) {
        'success' => 'alert-success',
        'danger', 'error' => 'alert-danger',
        'warning' => 'alert-warning',
        default => 'alert-info',
    };
@endphp
<div {{ $attributes->merge(['class' => "alert $classes"]) }} role="alert">
    @if($dismissible)
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    @endif
    {{ $slot }}
</div>
