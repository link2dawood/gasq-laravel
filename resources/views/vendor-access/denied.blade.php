@extends('layouts.app')
@section('title', 'Opportunity Unavailable')
@section('header_variant', 'dashboard')

@section('content')
<div class="min-vh-100 py-5 px-3" style="background:var(--gasq-background)">
  <div class="container" style="max-width:640px">
    <div class="card gasq-card">
      <div class="card-body p-4 p-md-5">
        <h1 class="h4 fw-bold mb-3">
          @switch($reason)
            @case('expired') This invitation has expired @break
            @case('revoked') This invitation was withdrawn @break
            @case('exhausted') This invitation has been used @break
            @case('not_verified') Verification required @break
            @case('not_a_vendor') Vendor account required @break
            @default This opportunity is not available to you
          @endswitch
        </h1>
        <p class="text-gasq-muted mb-4">{{ $message }}</p>
        <div class="d-flex flex-wrap gap-2">
          <a href="{{ route('contact') }}" class="btn btn-primary">Contact GASQ</a>
          <a href="{{ url('/home') }}" class="btn btn-outline-secondary">Back to dashboard</a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
