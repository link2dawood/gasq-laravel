@props(['height' => 56, 'class' => '', 'alt' => 'GASQ'])
@php
    $logoPath = \App\Models\Setting::get('site_logo');
    $logoUrl = $logoPath ? asset($logoPath) : asset('img/gasq-logo.png');
@endphp
<img src="{{ $logoUrl }}" alt="{{ $alt }}" class="{{ $class }}" style="height: {{ $height }}px; width: auto; max-height: none;" {{ $attributes }}>