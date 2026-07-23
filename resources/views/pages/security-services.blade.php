@extends('layouts.app')

@section('title', 'Types of Security Services')

@section('content')
<div class="container py-5" style="max-width: 960px;">
    <h1 class="fw-bold mb-2">Types of Security Services You Can Contract</h1>
    <p class="fs-5 text-gasq-muted mb-4">Not sure which service fits your site? Here are the security services buyers most often contract through GASQ — and what each one is for.</p>

    @php
        $services = config('security_services.services', []);
    @endphp

    <div class="row g-3">
        @foreach($services as $s)
            <div class="col-md-6">
                <div class="card gasq-card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start gap-3">
                            <span class="gasq-icon-badge" style="width:44px;height:44px;font-size:1.1rem"><i class="fa {{ $s['icon'] }}"></i></span>
                            <div>
                                <h2 class="h6 fw-bold mb-1">{{ $s['name'] }}</h2>
                                <p class="text-gasq-muted small mb-0">{{ $s['desc'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="text-center border rounded-3 p-4 p-md-5 mt-5" style="background:#f4f6fb;">
        <h3 class="h4 fw-bold mb-2">Not sure what it should cost?</h3>
        <p class="text-gasq-muted mb-4">Run an independent <strong>Cost to Protect&trade;</strong> estimate, then post your job to qualified vendors.</p>
        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
            <a href="{{ route('instant-estimator.index') }}" class="btn btn-primary btn-lg">Get an Instant Estimate</a>
            <a href="{{ route('jobs.create') }}" class="btn btn-outline-primary btn-lg">Post Your Job</a>
        </div>
    </div>
</div>
@endsection
