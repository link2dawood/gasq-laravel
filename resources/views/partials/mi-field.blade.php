{{--
  Partial: mi-field
  Variables:
    $id          — input id
    $label       — field label (HTML allowed)
    $help        — hint text below label
    $unit        — unit symbol (e.g. '%', '$', 'hrs')
    $unit_pos    — 'prefix' | 'suffix' (default: suffix)
    $step        — input step attribute
    $min         — input min attribute
    $max_slider  — range max
    $data_unit   — optional 'pct' (sets data-unit attribute on input)
--}}
@php
  $unitPos  = $unit_pos  ?? 'suffix';
  $dataUnit = $data_unit ?? null;
  $sliderMax = $max_slider ?? 100;
@endphp

<div class="mi-field">
  <div class="mi-field-row">
    <div class="mi-label-group">
      <label class="mi-label" for="{{ $id }}">{!! $label !!}</label>
      <div class="mi-help">{{ $help }}</div>
    </div>

    <div class="mi-input-wrap">
      @if($unitPos === 'prefix')
        <span class="mi-unit" style="border-right:none;border-radius:var(--mi-radius-sm) 0 0 var(--mi-radius-sm);border-left:1.5px solid var(--mi-border);">{{ $unit }}</span>
        <input
          type="number"
          class="mi-input"
          id="{{ $id }}"
          step="{{ $step ?? '0.01' }}"
          min="{{ $min ?? '0' }}"
          style="border-radius:0 var(--mi-radius-sm) var(--mi-radius-sm) 0 !important; border-left:none !important;"
          @if($dataUnit) data-unit="{{ $dataUnit }}" @endif
        >
      @else
        <input
          type="number"
          class="mi-input"
          id="{{ $id }}"
          step="{{ $step ?? '0.01' }}"
          min="{{ $min ?? '0' }}"
          @if($dataUnit) data-unit="{{ $dataUnit }}" max="200" @endif
        >
        <span class="mi-unit">{{ $unit }}</span>
      @endif
    </div>
  </div>

  <div class="mi-slider-track">
    <input
      type="range"
      class="mi-range"
      min="{{ $min ?? '0' }}"
      max="{{ $sliderMax }}"
      step="{{ $step ?? '0.01' }}"
      data-sync="{{ $id }}"
    >
  </div>
</div>
