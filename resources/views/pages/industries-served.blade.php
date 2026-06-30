@extends('layouts.app')

@section('title', 'Industries That Need Security Services')

@section('content')
<div class="container py-5" style="max-width: 960px;">
    <h1 class="fw-bold mb-2">Industries That Need Contract Security</h1>
    <p class="fs-5 text-gasq-muted mb-4">Buyers across these industries come to GASQ to price and procure security services. Register as a vendor to respond to qualified job offers in the sectors you serve.</p>

    @php
        $industries = [
            ['icon' => 'fa-building',        'name' => 'Commercial Real Estate & Property Management', 'desc' => 'Office towers, mixed-use, and managed portfolios needing lobby, access control, and patrol coverage.'],
            ['icon' => 'fa-hospital',        'name' => 'Healthcare & Hospital Systems',                'desc' => 'Hospitals, clinics, and behavioral health facilities with 24/7 coverage and specialized de-escalation needs.'],
            ['icon' => 'fa-graduation-cap',  'name' => 'Education (K-12 & Universities)',              'desc' => 'Campuses requiring visitor screening, access control, event coverage, and emergency response.'],
            ['icon' => 'fa-landmark',        'name' => 'Government & Municipal Facilities',            'desc' => 'Public buildings, courts, and agencies with compliance-driven (SCA/PWA) staffing requirements.'],
            ['icon' => 'fa-industry',        'name' => 'Manufacturing & Distribution',                'desc' => 'Plants, warehouses, and logistics hubs protecting assets, inventory, and gate/dock access.'],
            ['icon' => 'fa-bolt',            'name' => 'Critical Infrastructure & Utilities',         'desc' => 'Power, water, and telecom sites with high-security and continuous-coverage demands.'],
            ['icon' => 'fa-martini-glass',   'name' => 'Hospitality & Entertainment',                 'desc' => 'Hotels, resorts, casinos, and venues balancing guest experience with safety and crowd control.'],
            ['icon' => 'fa-server',          'name' => 'Data Centers & Technology',                   'desc' => 'Facilities with strict access control, screening, and asset-protection protocols.'],
            ['icon' => 'fa-bag-shopping',    'name' => 'Retail & Shopping Centers',                   'desc' => 'Stores and malls focused on loss prevention, customer safety, and parking patrol.'],
            ['icon' => 'fa-helmet-safety',   'name' => 'Construction Sites',                          'desc' => 'Active sites needing equipment protection, access control, and after-hours patrol.'],
            ['icon' => 'fa-house',           'name' => 'Residential Communities & HOAs',              'desc' => 'Gated communities and multifamily properties needing gate, patrol, and amenity coverage.'],
            ['icon' => 'fa-building-columns','name' => 'Banking & Financial Services',                'desc' => 'Branches and operations centers with cash-handling and elevated-risk requirements.'],
        ];
    @endphp

    <div class="row g-3">
        @foreach($industries as $i)
            <div class="col-md-6">
                <div class="card gasq-card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start gap-3">
                            <span class="gasq-icon-badge" style="width:44px;height:44px;font-size:1.1rem"><i class="fa {{ $i['icon'] }}"></i></span>
                            <div>
                                <h2 class="h6 fw-bold mb-1">{{ $i['name'] }}</h2>
                                <p class="text-gasq-muted small mb-0">{{ $i['desc'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="text-center border rounded-3 p-4 p-md-5 mt-5" style="background:#f4f6fb;">
        <h3 class="h4 fw-bold mb-2">Win work in the industries you serve</h3>
        <p class="text-gasq-muted mb-4">Register as a vendor to respond to qualified, buyer-controlled job offers.</p>
        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
            <a href="{{ route('register.vendor.index') }}" class="btn btn-primary btn-lg">Register as a Vendor</a>
            <a href="{{ route('job-board') }}" class="btn btn-outline-primary btn-lg">Browse the Job Board</a>
        </div>
    </div>
</div>
@endsection
