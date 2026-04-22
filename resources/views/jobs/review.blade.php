@extends('layouts.app')

@section('title', 'Review Job Announcement')

@section('content')
@php
    $questionnaire = $questionnaire ?? [];
    $serviceTypes = $questionnaire['service_types'] ?? [];
    $shiftsNeeded = $questionnaire['shifts_needed'] ?? [];
    $documents = $questionnaire['supporting_documents'] ?? [];
@endphp

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('job-board') }}">Job Board</a></li>
            <li class="breadcrumb-item">Buyer Questionnaire</li>
            <li class="breadcrumb-item active">Review Announcement</li>
        </ol>
    </nav>

    @if(session('error'))
        <x-alert type="danger" dismissible>{{ session('error') }}</x-alert>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h2 mb-2">Review Your Generated Job Announcement</h1>
            <p class="text-gasq-muted mb-0">
                GASQ built this announcement from the buyer questionnaire. Review it, make any changes you need, then publish it to qualified vendors.
            </p>
        </div>
        <div class="d-flex gap-2">
            <form action="{{ route('jobs.review.edit') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-secondary">Edit Questionnaire</button>
            </form>
            <form action="{{ route('jobs.publish') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary">Publish Job Announcement</button>
            </form>
        </div>
    </div>

    <x-card class="mb-4">
        <div class="small text-uppercase text-gasq-muted fw-semibold mb-2">Generated Announcement</div>
        <h2 class="h4 mb-3">{{ $preview['title'] ?? 'Security Services Request' }}</h2>
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="small text-uppercase text-gasq-muted fw-semibold">Service</div>
                <div>{{ $preview['category'] ?? 'Not provided' }}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-uppercase text-gasq-muted fw-semibold">Location</div>
                <div>{{ $preview['location'] ?? 'Not provided' }}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-uppercase text-gasq-muted fw-semibold">Start Date</div>
                <div>{{ $preview['service_start_date'] ?? 'Not provided' }}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-uppercase text-gasq-muted fw-semibold">Budget</div>
                <div>
                    @if(($preview['budget_min'] ?? null) !== null || ($preview['budget_max'] ?? null) !== null)
                        ${{ number_format((float) ($preview['budget_min'] ?? 0), 2) }} - ${{ number_format((float) ($preview['budget_max'] ?? 0), 2) }}
                    @else
                        Not provided
                    @endif
                </div>
            </div>
            <div class="col-md-4">
                <div class="small text-uppercase text-gasq-muted fw-semibold">Property Type</div>
                <div>{{ $preview['property_type'] ?? 'Not provided' }}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-uppercase text-gasq-muted fw-semibold">Guards per Shift</div>
                <div>{{ $preview['guards_per_shift'] ?? 'Not provided' }}</div>
            </div>
        </div>

        @if(! empty($preview['description']))
            <div class="small text-uppercase text-gasq-muted fw-semibold mb-2">Announcement Summary</div>
            <div class="mb-0">{!! nl2br(e($preview['description'])) !!}</div>
        @endif
    </x-card>

    <div class="row g-4">
        <div class="col-lg-6">
            <x-card title="Scope Snapshot" class="h-100">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Service Types</div>
                        <div>{{ $serviceTypes !== [] ? implode(', ', $serviceTypes) : 'Not provided' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Request Type</div>
                        <div>{{ ucwords(str_replace('_', ' ', (string) ($questionnaire['request_type'] ?? 'Not provided'))) }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Hours per Day</div>
                        <div>{{ $questionnaire['hours_per_day'] ?? 'Not provided' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Days per Week</div>
                        <div>{{ $questionnaire['days_per_week'] ?? 'Not provided' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Weeks per Year</div>
                        <div>{{ $questionnaire['weeks_per_year'] ?? 'Not provided' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Shifts Needed</div>
                        <div>{{ $shiftsNeeded !== [] ? implode(', ', $shiftsNeeded) : 'Not provided' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Assignment Type</div>
                        <div>{{ ucwords(str_replace('_', ' ', (string) ($questionnaire['assignment_type'] ?? 'Not provided'))) }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Contract Term</div>
                        <div>{{ $questionnaire['desired_contract_term'] ?? 'Not provided' }}</div>
                    </div>
                </div>
            </x-card>
        </div>
        <div class="col-lg-6">
            <x-card title="Buyer Qualification Snapshot" class="h-100">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Decision Maker</div>
                        <div>{{ ucwords(str_replace('_', ' ', (string) ($questionnaire['final_decision_maker'] ?? 'Not provided'))) }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Budget Approved</div>
                        <div>{{ ucwords(str_replace('_', ' ', (string) ($questionnaire['funds_approval_status'] ?? 'Not provided'))) }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Ready to Move Forward</div>
                        <div>{{ ucwords(str_replace('_', ' ', (string) ($questionnaire['move_forward_if_accepted'] ?? 'Not provided'))) }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Current Security Setup</div>
                        <div>{{ ucwords(str_replace('_', ' ', (string) ($questionnaire['current_security_setup'] ?? 'Not provided'))) }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Multiple Locations</div>
                        <div>{{ ($questionnaire['multiple_locations'] ?? '') === 'yes' ? 'Yes' : 'No' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Supporting Documents</div>
                        <div>{{ count($documents) }} uploaded</div>
                    </div>
                    <div class="col-12">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Primary Reason</div>
                        <div>{{ $questionnaire['primary_reason'] ?? 'Not provided' }}</div>
                    </div>
                    <div class="col-12">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Additional Vendor Notes</div>
                        <div>{{ $questionnaire['additional_notes_to_vendors'] ?? 'None provided' }}</div>
                    </div>
                </div>
            </x-card>
        </div>
    </div>

    @if(! empty($preview['special_requirements']))
        <x-card title="Special Requirements" class="mt-4">
            <ul class="mb-0">
                @foreach($preview['special_requirements'] as $requirement)
                    <li>{{ $requirement }}</li>
                @endforeach
            </ul>
        </x-card>
    @endif
</div>
@endsection
