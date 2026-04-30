@extends('layouts.app')

@section('title', $vendor->name . ' — Vendor Profile')

@php
    $profile = $vendor->vendorProfile;
    $capability = $vendor->vendorCapability;
    $additionalInfo = is_array($capability?->additional_info) ? $capability->additional_info : [];
    $generalInsurance = is_array(data_get($additionalInfo, 'insurance.general')) ? data_get($additionalInfo, 'insurance.general') : [];
    $workersCompInsurance = is_array(data_get($additionalInfo, 'insurance.workers_comp')) ? data_get($additionalInfo, 'insurance.workers_comp') : [];
    $services = array_values(array_filter($profile?->capabilities ?? $capability?->core_competencies ?? []));
    $certifications = array_values(array_filter(data_get($additionalInfo, 'certifications_flags', $capability?->certifications ?? [])));
    $serviceAreas = array_values(array_filter($capability?->service_areas ?? []));
    $statesLicensed = array_values(array_filter($capability?->states_licensed ?? []));
    $branchScope = match (data_get($additionalInfo, 'branch_office_scope')) {
        'local_only' => 'Local Only',
        'statewide' => 'Statewide',
        'nationwide' => 'Nationwide',
        default => null,
    };
    $locationLine = trim(implode(', ', array_filter([$vendor->city, $vendor->state, $vendor->zip_code])));
    $yesNo = static fn ($value) => match ($value) {
        'yes' => 'Yes',
        'no' => 'No',
        default => 'Not provided',
    };
    $operationRows = [
        'Works in other states' => $yesNo(data_get($additionalInfo, 'works_other_states')),
        'GPS guard monitoring' => $yesNo(data_get($additionalInfo, 'uses_gps_monitoring')),
        'Guard force management software' => $yesNo(data_get($additionalInfo, 'uses_guard_management_software')),
        'Tasers or similar non-lethal weapons' => $yesNo(data_get($additionalInfo, 'uses_tasers')),
        'Body cameras' => $yesNo(data_get($additionalInfo, 'uses_body_cameras')),
        'Real-time incident reporting software' => $yesNo(data_get($additionalInfo, 'uses_incident_reporting_software')),
        'Drones in operation' => $yesNo(data_get($additionalInfo, 'uses_drones')),
        'Uses 1099 employees' => $yesNo(data_get($additionalInfo, 'uses_1099_employees')),
        '24-hour dispatch center' => $yesNo(data_get($additionalInfo, 'has_dispatch_center')),
    ];
@endphp

@push('styles')
<style>
    .vendor-public-shell {
        background:
            radial-gradient(circle at top right, rgba(31, 111, 255, 0.08), transparent 28rem),
            linear-gradient(180deg, #f7f8fc 0%, #f3f5fa 100%);
        min-height: calc(100vh - 72px);
    }
    .vendor-public-hero {
        border: 1px solid #dde4ef;
        border-radius: 1.2rem;
        background: rgba(255, 255, 255, 0.92);
        box-shadow: 0 18px 45px rgba(20, 30, 55, 0.05);
        overflow: hidden;
    }
    .vendor-public-card {
        border: 1px solid #dde4ef;
        border-radius: 1rem;
        background: #fff;
        box-shadow: 0 10px 28px rgba(20, 30, 55, 0.04);
        height: 100%;
    }
    .vendor-public-card .card-body {
        padding: 1.15rem 1.2rem 1.25rem;
    }
    .vendor-public-chip {
        display: inline-flex;
        align-items: center;
        padding: .45rem .75rem;
        border-radius: 999px;
        background: #f4f7fd;
        border: 1px solid #dce4f3;
        color: #25324c;
        font-size: .92rem;
        font-weight: 600;
    }
    .vendor-public-grid {
        display: grid;
        gap: .9rem;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .vendor-public-item {
        padding: .75rem .85rem;
        border-radius: .9rem;
        background: #fafbfd;
        border: 1px solid #e3e8f2;
    }
    .vendor-public-label {
        display: block;
        margin-bottom: .18rem;
        color: #6d7690;
        font-size: .84rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    .vendor-public-value {
        color: #273147;
        font-size: .98rem;
        line-height: 1.45;
    }
    .vendor-public-list {
        display: grid;
        gap: .7rem;
    }
    @media (max-width: 767.98px) {
        .vendor-public-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="vendor-public-shell py-4">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('job-board') }}">Job Board</a></li>
                <li class="breadcrumb-item active">Vendor Profile</li>
            </ol>
        </nav>

        <div class="vendor-public-hero p-4 p-lg-5 mb-4">
            <div class="row align-items-center g-4">
                <div class="col-lg-3 text-center text-lg-start">
                    <img src="{{ $vendor->avatar_url }}" alt="" class="rounded-circle border border-3 border-white shadow-sm" width="128" height="128">
                </div>
                <div class="col-lg-9">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <h1 class="display-5 fw-bold mb-0">{{ $vendor->name }}</h1>
                        @if($profile?->is_verified)
                            <span class="badge bg-success">Verified Vendor</span>
                        @endif
                        @if($capability?->license_verified)
                            <span class="badge bg-primary">License Verified</span>
                        @endif
                        @if($capability?->insurance_verified)
                            <span class="badge bg-info text-dark">Insurance Verified</span>
                        @endif
                    </div>
                    @if($profile?->company_name || $vendor->company)
                        <p class="fs-4 text-gasq-muted mb-2">{{ $profile?->company_name ?: $vendor->company }}</p>
                    @endif
                    @if($profile?->description)
                        <p class="mb-3 fs-5">{{ $profile->description }}</p>
                    @endif
                    <div class="d-flex flex-wrap gap-2">
                        @if($profile?->phone || $vendor->phone)
                            <span class="vendor-public-chip"><i class="fa fa-phone me-2"></i>{{ $profile?->phone ?: $vendor->phone }}</span>
                        @endif
                        @if($vendor->email)
                            <span class="vendor-public-chip"><i class="fa fa-envelope me-2"></i>{{ $vendor->email }}</span>
                        @endif
                        @if($locationLine !== '')
                            <span class="vendor-public-chip"><i class="fa fa-location-dot me-2"></i>{{ $locationLine }}</span>
                        @endif
                        @if($branchScope)
                            <span class="vendor-public-chip"><i class="fa fa-building me-2"></i>{{ $branchScope }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if($profile || $capability)
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="vendor-public-card card">
                        <div class="card-body">
                            <h2 class="h4 fw-bold mb-3">Company Details</h2>
                            <div class="vendor-public-grid">
                                @if($profile?->company_name || $vendor->company)
                                    <div class="vendor-public-item">
                                        <span class="vendor-public-label">Company Name</span>
                                        <div class="vendor-public-value">{{ $profile?->company_name ?: $vendor->company }}</div>
                                    </div>
                                @endif
                                @if($capability?->legal_business_name)
                                    <div class="vendor-public-item">
                                        <span class="vendor-public-label">Legal Business Name</span>
                                        <div class="vendor-public-value">{{ $capability->legal_business_name }}</div>
                                    </div>
                                @endif
                                @if($capability?->business_license_number)
                                    <div class="vendor-public-item">
                                        <span class="vendor-public-label">PPO License Number</span>
                                        <div class="vendor-public-value">{{ $capability->business_license_number }}</div>
                                    </div>
                                @endif
                                @if(data_get($additionalInfo, 'license_expiration_date'))
                                    <div class="vendor-public-item">
                                        <span class="vendor-public-label">License Expiration</span>
                                        <div class="vendor-public-value">{{ data_get($additionalInfo, 'license_expiration_date') }}</div>
                                    </div>
                                @endif
                                @if(data_get($additionalInfo, 'vendor_ein'))
                                    <div class="vendor-public-item">
                                        <span class="vendor-public-label">Vendor EIN</span>
                                        <div class="vendor-public-value">{{ data_get($additionalInfo, 'vendor_ein') }}</div>
                                    </div>
                                @endif
                                @if($capability?->years_of_experience)
                                    <div class="vendor-public-item">
                                        <span class="vendor-public-label">Years in Business</span>
                                        <div class="vendor-public-value">{{ $capability->years_of_experience }}</div>
                                    </div>
                                @endif
                                @if($profile?->address)
                                    <div class="vendor-public-item">
                                        <span class="vendor-public-label">Street Address</span>
                                        <div class="vendor-public-value">{{ $profile->address }}</div>
                                    </div>
                                @endif
                                @if($locationLine !== '')
                                    <div class="vendor-public-item">
                                        <span class="vendor-public-label">City / State / ZIP</span>
                                        <div class="vendor-public-value">{{ $locationLine }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="vendor-public-card card">
                        <div class="card-body">
                            <h2 class="h4 fw-bold mb-3">Services & Coverage</h2>
                            @if($services !== [])
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    @foreach($services as $service)
                                        <span class="vendor-public-chip">{{ $service }}</span>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gasq-muted mb-3">No service capabilities published yet.</p>
                            @endif

                            <div class="vendor-public-grid">
                                @if($serviceAreas !== [])
                                    <div class="vendor-public-item">
                                        <span class="vendor-public-label">Service Areas</span>
                                        <div class="vendor-public-value">{{ implode(', ', $serviceAreas) }}</div>
                                    </div>
                                @endif
                                @if($statesLicensed !== [])
                                    <div class="vendor-public-item">
                                        <span class="vendor-public-label">States Licensed</span>
                                        <div class="vendor-public-value">{{ implode(', ', $statesLicensed) }}</div>
                                    </div>
                                @endif
                                @if(data_get($additionalInfo, 'full_time_employees') !== null)
                                    <div class="vendor-public-item">
                                        <span class="vendor-public-label">Full Time Employees</span>
                                        <div class="vendor-public-value">{{ data_get($additionalInfo, 'full_time_employees') }}</div>
                                    </div>
                                @endif
                                @if(data_get($additionalInfo, 'part_time_employees') !== null)
                                    <div class="vendor-public-item">
                                        <span class="vendor-public-label">Part Time Employees</span>
                                        <div class="vendor-public-value">{{ data_get($additionalInfo, 'part_time_employees') }}</div>
                                    </div>
                                @endif
                                @if($branchScope)
                                    <div class="vendor-public-item">
                                        <span class="vendor-public-label">Branch Office Coverage</span>
                                        <div class="vendor-public-value">{{ $branchScope }}</div>
                                    </div>
                                @endif
                                <div class="vendor-public-item">
                                    <span class="vendor-public-label">Works in Other States</span>
                                    <div class="vendor-public-value">{{ $yesNo(data_get($additionalInfo, 'works_other_states')) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="vendor-public-card card">
                        <div class="card-body">
                            <h2 class="h4 fw-bold mb-3">General Liability & E&O Insurance</h2>
                            <div class="vendor-public-list">
                                @php
                                    $generalRows = [
                                        'Limits Covered' => data_get($generalInsurance, 'limits_covered'),
                                        'Deductible per Occurrence' => data_get($generalInsurance, 'deductible_per_occurrence'),
                                        'Insurance Company Name' => data_get($generalInsurance, 'company_name'),
                                        'Insurance Company Address' => data_get($generalInsurance, 'company_address'),
                                        'Policy Number' => data_get($generalInsurance, 'policy_number'),
                                        'Carrier Phone Number' => data_get($generalInsurance, 'carrier_phone'),
                                        'Agent to Contact' => data_get($generalInsurance, 'agent_name'),
                                        'Agent Email Address' => data_get($generalInsurance, 'agent_email'),
                                    ];
                                @endphp
                                @forelse(array_filter($generalRows) as $label => $value)
                                    <div class="vendor-public-item">
                                        <span class="vendor-public-label">{{ $label }}</span>
                                        <div class="vendor-public-value">{{ $value }}</div>
                                    </div>
                                @empty
                                    <p class="text-gasq-muted mb-0">Insurance details have not been published yet.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="vendor-public-card card">
                        <div class="card-body">
                            <h2 class="h4 fw-bold mb-3">Workmans Comp Insurance</h2>
                            <div class="vendor-public-list">
                                @php
                                    $workersRows = [
                                        'Insurance Company Name' => data_get($workersCompInsurance, 'company_name'),
                                        'Insurance Company Address' => data_get($workersCompInsurance, 'company_address'),
                                        'Policy Number' => data_get($workersCompInsurance, 'policy_number'),
                                        'Carrier Phone Number' => data_get($workersCompInsurance, 'carrier_phone'),
                                        'Agent to Contact' => data_get($workersCompInsurance, 'agent_name'),
                                        'Agent Email Address' => data_get($workersCompInsurance, 'agent_email'),
                                    ];
                                @endphp
                                @forelse(array_filter($workersRows) as $label => $value)
                                    <div class="vendor-public-item">
                                        <span class="vendor-public-label">{{ $label }}</span>
                                        <div class="vendor-public-value">{{ $value }}</div>
                                    </div>
                                @empty
                                    <p class="text-gasq-muted mb-0">Workers comp insurance details have not been published yet.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="vendor-public-card card">
                        <div class="card-body">
                            <h2 class="h4 fw-bold mb-3">Operations Snapshot</h2>
                            <div class="vendor-public-list">
                                @foreach($operationRows as $label => $value)
                                    <div class="vendor-public-item">
                                        <span class="vendor-public-label">{{ $label }}</span>
                                        <div class="vendor-public-value">{{ $value }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="vendor-public-card card">
                        <div class="card-body">
                            <h2 class="h4 fw-bold mb-3">Certifications & Business Status</h2>
                            @if($certifications !== [])
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($certifications as $certification)
                                        <span class="vendor-public-chip">{{ ucwords(str_replace('_', ' ', $certification)) }}</span>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gasq-muted mb-0">No certifications or business status flags have been published yet.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="vendor-public-card card">
                <div class="card-body">
                    <p class="text-muted mb-0">This vendor has not completed their profile yet.</p>
                    <p class="mb-0 mt-2">Contact: {{ $vendor->email }}@if($vendor->phone) · {{ $vendor->phone }}@endif</p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
