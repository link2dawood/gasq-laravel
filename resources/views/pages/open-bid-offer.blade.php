@extends('layouts.app')

@section('title', 'Email Summary Notification to Security Vendor')

@section('content')
@php
    $fmtMoney = fn ($value) => $value !== null ? '$' . number_format((float) $value, 2) : 'Not provided';
    $fmtInt = fn ($value) => $value !== null ? number_format((int) $value) : 'Not provided';
    $yesNo = fn ($value) => $value ? 'Yes' : 'No';
@endphp

<div class="py-5" style="background: linear-gradient(135deg, rgba(13,110,253,0.12) 0%, rgba(255,255,255,1) 60%);">
    <div class="container">
        <div class="text-center mb-4">
            <h1 class="display-6 fw-bold mb-2">Email Summary Notification to Security Vendor</h1>
            <p class="lead text-dark mb-3">
                ALERT! GASQNOW New Security Project in {{ $city ?? 'N/A' }}@if(!empty($state)), {{ $state }}@endif
            </p>
            <div class="row g-3 justify-content-center text-start mx-auto" style="max-width: 960px;">
                <div class="col-md-4">
                    <p class="mb-1"><strong>Type of Service Requested:</strong></p>
                    <p class="text-gasq-muted mb-0">{{ $serviceTypeSummary ?? 'Not provided' }}</p>
                </div>
                <div class="col-md-4">
                    <p class="mb-1"><strong>Email Address:</strong></p>
                    <p class="text-gasq-muted mb-0">{{ $maskedBuyerEmail }}</p>
                </div>
                <div class="col-md-4">
                    <p class="mb-1"><strong>Phone Number:</strong></p>
                    <p class="text-gasq-muted mb-0">{{ $maskedBuyerPhone }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    @if(! $pageHasData)
        <div class="alert alert-info">
            No bid summary is available yet for your account. Open a job with bids first, or pass a bid id like
            <code>?bid=123</code>.
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4 p-lg-5">
            <h2 class="h4 fw-bold mb-4">Project Overview</h2>
            <div class="row g-4">
                <div class="col-md-6">
                    <p class="mb-2"><strong>Type of Service Requested:</strong></p>
                    <p class="text-gasq-muted mb-0">{{ $serviceTypeSummary ?? 'Not provided' }}</p>
                </div>
                <div class="col-md-6">
                    <p class="mb-2"><strong>Total Bid Offer Value:</strong></p>
                    <p class="text-gasq-muted mb-0">{{ $fmtMoney($bidOfferValue) }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4 p-lg-5">
            <h2 class="h4 fw-bold mb-4">Buyer Contact & Validation</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <p class="mb-1"><strong>Email Address:</strong></p>
                    <p class="text-gasq-muted mb-0">{{ $maskedBuyerEmail }}</p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1"><strong>Phone Number:</strong></p>
                    <p class="text-gasq-muted mb-0">{{ $maskedBuyerPhone }}</p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1"><strong>Phone Number Verified:</strong></p>
                    <p class="text-gasq-muted mb-0">{{ $yesNo($phoneVerified) }}</p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1"><strong>Decision Maker Verified/Validated:</strong></p>
                    <p class="text-gasq-muted mb-0">{{ $yesNo($decisionMakerValidated) }}</p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1"><strong>Budget Amount:</strong></p>
                    <p class="text-gasq-muted mb-0">{{ $budgetAmountText ?? 'Not provided' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4 p-lg-5">
            <h2 class="h4 fw-bold mb-4">Validation and Offer Summary</h2>
            <div class="row g-3">
                <div class="col-md-4">
                    <p class="mb-1"><strong>City:</strong></p>
                    <p class="text-gasq-muted mb-0">{{ $city ?? 'Not provided' }}</p>
                </div>
                <div class="col-md-4">
                    <p class="mb-1"><strong>State:</strong></p>
                    <p class="text-gasq-muted mb-0">{{ $state ?? 'Not provided' }}</p>
                </div>
                <div class="col-md-4">
                    <p class="mb-1"><strong>Zip Code:</strong></p>
                    <p class="text-gasq-muted mb-0">{{ $zipCode ?? 'Not provided' }}</p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1"><strong>Total Credits to Respond:</strong></p>
                    <p class="text-gasq-muted mb-0">{{ $fmtInt($creditsToRespond) }}</p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1"><strong>Responses:</strong></p>
                    <p class="text-gasq-muted mb-0">{{ $acceptedBidsCount }}/{{ $responseDenominator }} Professionals have accepted bid offer</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4 p-lg-5">
            <h2 class="h4 fw-bold mb-4">Project Details</h2>
            <div class="vstack gap-3">
                @foreach($projectDetails as $detail)
                    <div>
                        <p class="mb-1"><strong>{{ $detail['label'] }}</strong></p>
                        <p class="text-gasq-muted mb-0">{{ $detail['value'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4 p-lg-5">
            <h2 class="h4 fw-bold mb-4">Coverage Summary</h2>
            <div class="row g-3">
                <div class="col-md-4">
                    <p class="mb-1"><strong>Total Hours per Day of Coverage:</strong></p>
                    <p class="text-gasq-muted mb-0">{{ $fmtInt($hoursPerDay) }}</p>
                </div>
                <div class="col-md-4">
                    <p class="mb-1"><strong>Total Days per Week of Coverage:</strong></p>
                    <p class="text-gasq-muted mb-0">{{ $fmtInt($daysPerWeek) }}</p>
                </div>
                <div class="col-md-4">
                    <p class="mb-1"><strong>Total Weekly Hours Hired to Work:</strong></p>
                    <p class="text-gasq-muted mb-0">{{ $fmtInt($weeklyHours) }}</p>
                </div>
                <div class="col-md-4">
                    <p class="mb-1"><strong>Total Monthly Hours Hired to Work:</strong></p>
                    <p class="text-gasq-muted mb-0">{{ $fmtInt($monthlyHours) }}</p>
                </div>
                <div class="col-md-4">
                    <p class="mb-1"><strong>Total Number of Weeks:</strong></p>
                    <p class="text-gasq-muted mb-0">{{ $fmtInt($totalWeeks) }}</p>
                </div>
                <div class="col-md-4">
                    <p class="mb-1"><strong>Total Months of Coverage:</strong></p>
                    <p class="text-gasq-muted mb-0">{{ $fmtInt($totalMonths) }}</p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1"><strong>Total Staff Required:</strong></p>
                    <p class="text-gasq-muted mb-0">{{ $fmtInt($staffRequired) }}</p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1"><strong>Total Term/Annual Hours:</strong></p>
                    <p class="text-gasq-muted mb-0">{{ $fmtInt($annualHours) }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center">
        @if($job)
            <a class="btn btn-primary btn-lg px-5" href="{{ route('jobs.show', $job) }}">I ACCEPT BID OFFER</a>
        @else
            <a class="btn btn-primary btn-lg px-5" href="{{ route('jobs.index') }}">View Available Jobs</a>
        @endif
    </div>
</div>
@endsection
