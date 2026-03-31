@extends('layouts.app')

@section('header_variant', 'dashboard')

@section('title', 'Open Bid Offer')

@section('content')
<div class="container py-5">
    <div class="text-center mb-5">
        <div class="d-inline-block px-4 py-2 rounded-pill bg-secondary text-white mb-4">
            UI Preview Only
        </div>
        <h1 class="display-4 fw-bold mb-3">Open Bid Offer</h1>
        <p class="lead text-gasq-muted mx-auto" style="max-width: 900px;">
            Marketing preview for the bid-offer workflow. Listings and bids run in Laravel—browse the job board or post
            a job when you are signed in.
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap mt-4">
            <a class="btn btn-primary btn-lg" href="{{ route('job-board') }}">Browse Job Board</a>
            @auth
                <a class="btn btn-outline-primary btn-lg" href="{{ route('jobs.create') }}">Post a Job</a>
            @else
                <a class="btn btn-outline-primary btn-lg" href="{{ route('register') }}">Create Account</a>
            @endauth
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <x-card title="For buyers" subtitle="Request compliant bids">
                <p class="text-gasq-muted small mb-3">
                    Publish scope, coverage, and evaluation criteria so vendors respond on one platform.
                </p>
                <a class="btn btn-sm btn-outline-primary" href="{{ url('/gasq-tco-calculator') }}">TCO preview</a>
            </x-card>
        </div>
        <div class="col-md-4">
            <x-card title="For vendors" subtitle="Clear requirements">
                <p class="text-gasq-muted small mb-3">
                    Review open opportunities and submit structured pricing without chasing email threads.
                </p>
                <a class="btn btn-sm btn-outline-primary" href="{{ url('/vendor-form') }}">Vendor form (UI)</a>
            </x-card>
        </div>
        <div class="col-md-4">
            <x-card title="Sample KPI strip" subtitle="Static figures">
                <div class="d-flex justify-content-between small py-1 border-bottom">
                    <span class="text-gasq-muted">Example sealed bids</span>
                    <span class="font-monospace fw-semibold">4</span>
                </div>
                <div class="d-flex justify-content-between small py-1 border-bottom">
                    <span class="text-gasq-muted">Example response time</span>
                    <span class="font-monospace fw-semibold">72 hrs</span>
                </div>
                <div class="d-flex justify-content-between small py-1">
                    <span class="text-gasq-muted">Evaluation</span>
                    <span class="font-monospace fw-semibold">Weighted score</span>
                </div>
            </x-card>
        </div>
    </div>
</div>
@endsection
