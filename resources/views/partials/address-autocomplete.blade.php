@php
    /**
     * Reusable Google Places address autocomplete.
     *
     * Required:
     *   $name        — form input name (e.g. 'business_address')
     *   $suffix      — unique DOM id suffix (e.g. 'biz', 'street'); two instances on the same page must differ
     *
     * Optional:
     *   $value       — current address value
     *   $placeId     — current place_id value (round-tripped on validation errors)
     *   $label       — visible label (omit/empty for none)
     *   $required    — bool, adds `required` attribute (default false)
     *   $placeholder — input placeholder
     *   $errorKey    — error bag key (defaults to $name)
     */
    $mapsKey = config('services.google.maps_api_key');
    $value = $value ?? '';
    $placeId = $placeId ?? '';
    $label = $label ?? null;
    $required = $required ?? false;
    $placeholder = $placeholder ?? 'Start typing an address…';
    $errorKey = $errorKey ?? $name;
    $inputId = 'addr-' . $suffix;
    $placeIdName = $name . '_place_id';
    $callback = 'initAddressAutocomplete_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $suffix);
@endphp

<div>
    @if($label)
        <label class="form-label" for="{{ $inputId }}">
            {{ $label }} @if($required)<span class="text-danger">*</span>@endif
        </label>
    @endif
    <input
        type="text"
        name="{{ $name }}"
        id="{{ $inputId }}"
        class="form-control @error($errorKey) is-invalid @enderror"
        value="{{ $value }}"
        placeholder="{{ $placeholder }}"
        autocomplete="off"
        @if($required) required @endif
    >
    <input type="hidden" name="{{ $placeIdName }}" id="{{ $inputId }}-pid" value="{{ $placeId }}">
    @error($errorKey)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    @error($placeIdName)
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
    @if($mapsKey)
        <div class="form-text">Pick an address from the suggestions to ensure it's a valid location.</div>
    @else
        <div class="form-text text-warning">Address validation disabled — Google Maps API key not configured.</div>
    @endif
</div>

@if($mapsKey)
@push('scripts')
<script>
window['{{ $callback }}'] = function () {
    const input = document.getElementById('{{ $inputId }}');
    const pidInput = document.getElementById('{{ $inputId }}-pid');
    if (!input || !window.google || !google.maps || !google.maps.places) {
        return;
    }
    const ac = new google.maps.places.Autocomplete(input, {
        fields: ['place_id', 'formatted_address'],
        types: ['address'],
    });
    ac.addListener('place_changed', function () {
        const place = ac.getPlace();
        if (place && place.place_id) {
            pidInput.value = place.place_id;
            if (place.formatted_address) {
                input.value = place.formatted_address;
            }
        }
    });
    // Whenever the user edits the field by hand, invalidate the previously-selected place_id
    // so the server-side check rejects free-text edits.
    input.addEventListener('input', function () {
        pidInput.value = '';
    });
};
</script>
<script src="https://maps.googleapis.com/maps/api/js?key={{ $mapsKey }}&libraries=places&callback={{ $callback }}" async defer></script>
@endpush
@endif
