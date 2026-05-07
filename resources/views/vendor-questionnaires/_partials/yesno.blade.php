@php
    /** @var string $name */
    /** @var string $label */
    /** @var array $responses */
    $current = $responses[$name] ?? null;
    $required = $required ?? false;
@endphp
<div class="mb-3">
    <label class="form-label fw-semibold">
        {{ $label }} @if($required)<span class="text-danger">*</span>@endif
    </label>
    <div class="d-flex gap-3">
        <div class="form-check">
            <input class="form-check-input" type="radio"
                   id="{{ $name }}_yes" name="responses[{{ $name }}]" value="yes"
                   @checked($current === 'yes')>
            <label class="form-check-label" for="{{ $name }}_yes">Yes</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio"
                   id="{{ $name }}_no" name="responses[{{ $name }}]" value="no"
                   @checked($current === 'no')>
            <label class="form-check-label" for="{{ $name }}_no">No</label>
        </div>
    </div>
</div>
