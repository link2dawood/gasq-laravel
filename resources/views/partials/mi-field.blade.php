{{--
  Partial: mi-field
  Variables:
    $id          — input id
    $label       — field label (HTML allowed)
    $help        — hint text below label
    $unit        — unit symbol: '%', '$', 'hrs', etc.
    $unit_pos    — 'prefix' | 'suffix' (default: suffix)
    $step        — input step
    $min         — input min
    $max_slider  — range max
    $data_unit   — 'pct' if percent field
--}}
@php
  $unitPos  = $unit_pos  ?? 'suffix';
  $dataUnit = $data_unit ?? null;
  $sliderMax = $max_slider ?? 100;
  $isPct = $dataUnit === 'pct';
@endphp

<div class="mi-field-card">

  <div class="mi-field-row">
    <div>
      <div class="mi-label">{!! $label !!}</div>
      <div class="mi-help">{{ $help }}</div>
    </div>

    <div class="mi-input-group">
      @if($unitPos === 'prefix')
        <span class="mi-unit mi-unit-prefix">{{ $unit }}</span>
        <input
          type="number"
          id="{{ $id }}"
          class="mi-number-input has-prefix"
          step="{{ $step ?? '0.01' }}"
          min="{{ $min ?? '0' }}"
          @if($isPct) data-unit="pct" max="200" @endif
        >
      @else
        <input
          type="number"
          id="{{ $id }}"
          class="mi-number-input has-suffix"
          step="{{ $step ?? '0.01' }}"
          min="{{ $min ?? '0' }}"
          @if($isPct) data-unit="pct" max="200" @endif
        >
        <span class="mi-unit mi-unit-suffix">{{ $unit }}</span>
      @endif
    </div>
  </div>

  <input
    type="range"
    class="mi-range"
    min="{{ $min ?? '0' }}"
    max="{{ $sliderMax }}"
    step="{{ $step ?? '0.01' }}"
    data-sync="{{ $id }}"
    style="display:block;width:100%;margin-top:4px;"
  >

</div>
