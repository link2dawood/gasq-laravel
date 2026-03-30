{{--
  React calculator SPA (gasq-calculator-project) embedded in Laravel layout with navbar.
  Assets live under public/assets (same paths as react-ui/index.html).
--}}
@extends('layouts.app')

@section('title', $title ?? 'Calculator')

@section('main_class', 'py-0')

@section('content')
<div id="root" class="calculator-spa-root"></div>
@endsection

@push('styles')
<link rel="stylesheet" crossorigin href="{{ asset('assets/index-DGzybfxK.css') }}">
{{-- After SPA CSS: restore Bootstrap navbar + stacking (Tailwind can hide .collapse / paint over header) --}}
<link rel="stylesheet" href="{{ asset('css/gasq-spa-shell.css') }}">
<style>
    .calculator-spa-root { min-height: min(85vh, 920px); }
</style>
@endpush

@push('scripts')
<script type="module" crossorigin src="{{ asset('assets/index-Bx21dMi4.js') }}"></script>
@endpush
