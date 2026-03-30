@php
    $mapsKey = config('services.google.maps_api_key');
    $callback = 'initJobPlaces_' . $suffix;
    $inputId = 'job-location-' . $suffix;
    $locVal = $location ?? '';
    $latVal = $latitude !== null && $latitude !== '' ? (string) $latitude : '';
    $lngVal = $longitude !== null && $longitude !== '' ? (string) $longitude : '';
    $pidVal = $googlePlaceId ?? '';
@endphp
<div>
    <label class="form-label">Job site address</label>
    <input
        type="text"
        name="location"
        id="{{ $inputId }}"
        class="form-control @error('location') is-invalid @enderror"
        value="{{ $locVal }}"
        placeholder="Start typing an address…"
        autocomplete="off"
    >
    @error('location')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    <input type="hidden" name="latitude" id="{{ $inputId }}-lat" value="{{ $latVal }}">
    <input type="hidden" name="longitude" id="{{ $inputId }}-lng" value="{{ $lngVal }}">
    <input type="hidden" name="google_place_id" id="{{ $inputId }}-pid" value="{{ $pidVal }}">
    @if($mapsKey)
        <div class="form-text">Address search uses Google Places. Coordinates save with the job for the map on the job page.</div>
    @else
        <div class="form-text text-gasq-muted">Set <code>GOOGLE_MAPS_API_KEY</code> in <code>.env</code> to enable address search and the job location map.</div>
    @endif
    @error('latitude')
        <div class="text-danger small">{{ $message }}</div>
    @enderror
    @error('longitude')
        <div class="text-danger small">{{ $message }}</div>
    @enderror
    @if($mapsKey)
        <div id="{{ $inputId }}-map-wrap" class="mt-2 rounded border overflow-hidden" style="height: 220px; display: none; border-color: var(--gasq-border);" aria-hidden="true"></div>
    @endif
</div>

@if($mapsKey)
@push('scripts')
<script>
window['{{ $callback }}'] = function () {
    const input = document.getElementById('{{ $inputId }}');
    const wrap = document.getElementById('{{ $inputId }}-map-wrap');
    if (!input || !window.google || !google.maps || !google.maps.places) {
        return;
    }
    let previewMap = null;
    let previewMarker = null;
    function updatePreviewMap() {
        if (!wrap) {
            return;
        }
        const latField = document.getElementById('{{ $inputId }}-lat');
        const lngField = document.getElementById('{{ $inputId }}-lng');
        const lat = parseFloat(latField && latField.value);
        const lng = parseFloat(lngField && lngField.value);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            wrap.style.display = 'none';
            return;
        }
        wrap.style.display = 'block';
        const center = { lat: lat, lng: lng };
        if (!previewMap) {
            previewMap = new google.maps.Map(wrap, { zoom: 14, center: center, mapTypeControl: true });
            previewMarker = new google.maps.Marker({ map: previewMap, position: center, title: 'Job site' });
        } else {
            previewMap.setCenter(center);
            if (previewMarker) {
                previewMarker.setPosition(center);
            }
        }
    }
    const ac = new google.maps.places.Autocomplete(input, {
        fields: ['place_id', 'geometry', 'formatted_address'],
        types: ['address'],
    });
    ac.addListener('place_changed', function () {
        const place = ac.getPlace();
        if (!place.geometry || !place.geometry.location) {
            return;
        }
        document.getElementById('{{ $inputId }}-lat').value = place.geometry.location.lat();
        document.getElementById('{{ $inputId }}-lng').value = place.geometry.location.lng();
        document.getElementById('{{ $inputId }}-pid').value = place.place_id || '';
        if (place.formatted_address) {
            input.value = place.formatted_address;
        }
        updatePreviewMap();
    });
    input.addEventListener('change', function () {
        if (!input.value.trim()) {
            document.getElementById('{{ $inputId }}-lat').value = '';
            document.getElementById('{{ $inputId }}-lng').value = '';
            document.getElementById('{{ $inputId }}-pid').value = '';
            if (wrap) {
                wrap.style.display = 'none';
            }
        }
    });
    updatePreviewMap();
};
</script>
<script src="https://maps.googleapis.com/maps/api/js?key={{ $mapsKey }}&libraries=places&callback={{ $callback }}" async defer></script>
@endpush
@endif
