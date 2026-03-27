@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container py-4 px-4">
    <div class="mb-4">
        <h1 class="gasq-page-title mb-1">Dashboard</h1>
        <p class="gasq-page-subtitle mb-0">Welcome back, {{ Auth::user()->name }}!</p>
    </div>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card gasq-card mb-4">
        <div class="card-body p-4 p-lg-5">
            <h2 class="gasq-card-title-lg mb-2">Quick actions</h2>
            <p class="text-gasq-muted small mb-4">Manage your account and access calculators, jobs, and credits.</p>
            <div class="row g-3">
                <div class="col-sm-6 col-lg-4">
                    <a href="{{ route('profile.show') }}" class="text-decoration-none">
                        <div class="gasq-card card h-100 border p-3 d-flex align-items-center gap-3">
                            <span class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                                <i class="fa fa-user"></i>
                            </span>
                            <div class="min-w-0">
                                <div class="fw-semibold">Profile</div>
                                <div class="small text-gasq-muted">Update your information</div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <a href="{{ url('/main-menu-calculator') }}" class="text-decoration-none">
                        <div class="gasq-card card h-100 border p-3 d-flex align-items-center gap-3">
                            <span class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                                <i class="fa fa-calculator"></i>
                            </span>
                            <div class="min-w-0">
                                <div class="fw-semibold">Calculators</div>
                                <div class="small text-gasq-muted">Estimates & analysis</div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <a href="{{ route('job-board') }}" class="text-decoration-none">
                        <div class="gasq-card card h-100 border p-3 d-flex align-items-center gap-3">
                            <span class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                                <i class="fa fa-briefcase"></i>
                            </span>
                            <div class="min-w-0">
                                <div class="fw-semibold">Job Board</div>
                                <div class="small text-gasq-muted">Browse or post jobs</div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <a href="{{ route('account-balance') }}" class="text-decoration-none">
                        <div class="gasq-card card h-100 border p-3 d-flex align-items-center gap-3">
                            <span class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                                <i class="fa fa-wallet"></i>
                            </span>
                            <div class="min-w-0">
                                <div class="fw-semibold">Credits</div>
                                <div class="small text-gasq-muted">Balance & history</div>
                            </div>
                        </div>
                    </a>
                </div>
                @if(auth()->user()->isBuyer() || auth()->user()->isVendor())
                <div class="col-sm-6 col-lg-4">
                    <a href="{{ route('jobs.index') }}" class="text-decoration-none">
                        <div class="gasq-card card h-100 border p-3 d-flex align-items-center gap-3">
                            <span class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                                <i class="fa fa-list"></i>
                            </span>
                            <div class="min-w-0">
                                <div class="fw-semibold">My Jobs</div>
                                <div class="small text-gasq-muted">Your postings & bids</div>
                            </div>
                        </div>
                    </a>
                </div>
                @endif
                <div class="col-sm-6 col-lg-4">
                    <a href="{{ route('discovery-call.index') }}" class="text-decoration-none">
                        <div class="gasq-card card h-100 border p-3 d-flex align-items-center gap-3">
                            <span class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                                <i class="fa fa-phone-alt"></i>
                            </span>
                            <div class="min-w-0">
                                <div class="fw-semibold">Discovery Call</div>
                                <div class="small text-gasq-muted">Request a call</div>
                            </div>
                        </div>
                    </a>
                </div>
                @if(auth()->user()->isAdmin())
                <div class="col-sm-6 col-lg-4">
                    <a href="{{ route('admin.settings') }}" class="text-decoration-none">
                        <div class="gasq-card card h-100 border p-3 d-flex align-items-center gap-3">
                            <span class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                                <i class="fa fa-cog"></i>
                            </span>
                            <div class="min-w-0">
                                <div class="fw-semibold">Settings</div>
                                <div class="small text-gasq-muted">Site settings & logo</div>
                            </div>
                        </div>
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
