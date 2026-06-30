@extends('layouts.app')

@section('title', 'Types of Security Services')

@section('content')
<div class="container py-5" style="max-width: 960px;">
    <h1 class="fw-bold mb-2">Types of Security Services You Can Contract</h1>
    <p class="fs-5 text-gasq-muted mb-4">Not sure which service fits your site? Here are the security services buyers most often contract through GASQ — and what each one is for.</p>

    @php
        $services = [
            ['icon' => 'fa-user-shield',   'name' => 'Security Guard Services',  'desc' => 'Uniformed, unarmed officers providing visible deterrence, observation and reporting, access control, and customer assistance. The most common service for offices, residential communities, retail, and commercial properties.'],
            ['icon' => 'fa-shield-halved', 'name' => 'Armed Security Services',  'desc' => 'Licensed, armed officers for higher-risk environments or assets — cash handling, high-value inventory, sensitive facilities, or locations with an elevated threat profile.'],
            ['icon' => 'fa-car',           'name' => 'Mobile Patrol Services',   'desc' => 'Marked-vehicle patrols that cover multiple checkpoints or properties on a schedule. Cost-effective coverage for large sites, parking areas, or several locations that do not need a dedicated on-site officer.'],
            ['icon' => 'fa-user-tie',      'name' => 'Executive Protection',     'desc' => 'Close personal protection for executives, VIPs, and at-risk individuals — including advance work, secure transportation, and event coverage.'],
            ['icon' => 'fa-calendar-check','name' => 'Event Security',           'desc' => 'Crowd management, access control, bag checks, and incident response for concerts, conferences, sporting events, and private functions.'],
            ['icon' => 'fa-school',        'name' => 'School Security',          'desc' => 'Campus safety tailored to K-12 and higher education — visitor screening, access control, dismissal support, and emergency response.'],
            ['icon' => 'fa-hospital',      'name' => 'Hospital Security',        'desc' => 'Healthcare-specific coverage for emergency departments, behavioral health units, infant protection, and general campus safety, with de-escalation training.'],
            ['icon' => 'fa-fire',          'name' => 'Fire Watch Services',     'desc' => 'Temporary fire-safety patrols required when sprinkler systems, alarms, or other fire protection are impaired or offline — keeping you compliant and covered.'],
            ['icon' => 'fa-tags',          'name' => 'Loss Prevention',         'desc' => 'Retail and asset-protection officers focused on reducing shrink, deterring theft, and supporting investigations.'],
            ['icon' => 'fa-id-badge',      'name' => 'Access Control Officers',  'desc' => 'Officers dedicated to managing entry points — credential checks, visitor logs, deliveries, and lobby or gate control.'],
        ];
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
