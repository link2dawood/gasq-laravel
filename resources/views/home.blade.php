@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="py-4 px-3 px-md-4" style="background:var(--gasq-background);min-height:100vh">
<div class="container-xl">

  {{-- Welcome Banner --}}
  <div class="gasq-welcome-banner mb-4">
    <div class="position-relative" style="z-index:1">
      <div class="row align-items-center g-3">
        <div class="col-md">
          <div class="banner-label">Dashboard</div>
          <h1 class="h2 fw-bold mb-1">Welcome back, {{ Auth::user()->name }}!</h1>
          <p class="banner-sub mb-0">Manage your account and access security cost calculators.</p>
        </div>
        <div class="col-md-auto">
          <a href="{{ url('/main-menu-calculator') }}" class="gasq-btn-banner">
            <i class="fa fa-calculator"></i> Open Calculator
          </a>
        </div>
      </div>
    </div>
  </div>

  @if (session('status'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
      {{ session('status') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  {{-- Stats Row --}}
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="gasq-stat-card">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <div class="gasq-icon-badge"><i class="fa fa-wallet fa-sm"></i></div>
          <span class="badge rounded-pill" style="background:rgba(6,45,121,0.08);color:var(--gasq-primary);font-size:0.7rem;font-weight:500">Balance</span>
        </div>
        <div class="stat-value gasq-mono">{{ isset($walletBalance) ? $walletBalance : 0 }}</div>
        <div class="stat-sub">Credits available</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="gasq-stat-card">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <div class="gasq-icon-badge"><i class="fa fa-calculator fa-sm"></i></div>
          <span class="badge rounded-pill" style="background:rgba(6,45,121,0.08);color:var(--gasq-primary);font-size:0.7rem;font-weight:500">Tools</span>
        </div>
        <div class="stat-value">15+</div>
        <div class="stat-sub">Calculators available</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="gasq-stat-card">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <div class="gasq-icon-badge"><i class="fa fa-briefcase fa-sm"></i></div>
          <span class="badge rounded-pill" style="background:rgba(6,45,121,0.08);color:var(--gasq-primary);font-size:0.7rem;font-weight:500">Jobs</span>
        </div>
        <div class="stat-value" style="font-size:1.1rem;padding-top:4px">Job Board</div>
        <div class="stat-sub">Browse opportunities</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="gasq-stat-card">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <div class="gasq-icon-badge"><i class="fa fa-phone fa-sm"></i></div>
          <span class="badge rounded-pill" style="background:rgba(6,45,121,0.08);color:var(--gasq-primary);font-size:0.7rem;font-weight:500">Support</span>
        </div>
        <div class="stat-value" style="font-size:1.1rem;padding-top:4px">1-on-1</div>
        <div class="stat-sub">Discovery call</div>
      </div>
    </div>
  </div>

  {{-- Quick Actions --}}
  <div class="card gasq-card mb-4">
    <div class="card-header py-3 px-4">
      <div class="d-flex align-items-center gap-2 mb-1">
        <div class="gasq-icon-badge"><i class="fa fa-bolt fa-sm"></i></div>
        <h2 class="gasq-card-title-lg mb-0">Quick Actions</h2>
      </div>
      <p class="text-gasq-muted small mb-0">Manage your account and access calculators, jobs, and credits.</p>
    </div>
    <div class="card-body p-4">
      <div class="row g-3">

        <div class="col-sm-6 col-lg-4">
          <a href="{{ route('profile.show') }}" class="gasq-action-card">
            <span class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px">
              <i class="fa fa-user"></i>
            </span>
            <div>
              <div class="action-title">Profile</div>
              <div class="action-desc">Update your information</div>
            </div>
          </a>
        </div>

        <div class="col-sm-6 col-lg-4">
          <a href="{{ url('/main-menu-calculator') }}" class="gasq-action-card">
            <span class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px">
              <i class="fa fa-calculator"></i>
            </span>
            <div>
              <div class="action-title">Calculators</div>
              <div class="action-desc">Estimates &amp; analysis</div>
            </div>
          </a>
        </div>

        <div class="col-sm-6 col-lg-4">
          <a href="{{ route('job-board') }}" class="gasq-action-card">
            <span class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px">
              <i class="fa fa-briefcase"></i>
            </span>
            <div>
              <div class="action-title">Job Board</div>
              <div class="action-desc">Browse or post jobs</div>
            </div>
          </a>
        </div>

        <div class="col-sm-6 col-lg-4">
          <a href="{{ route('account-balance') }}" class="gasq-action-card">
            <span class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px">
              <i class="fa fa-wallet"></i>
            </span>
            <div>
              <div class="action-title">Credits</div>
              <div class="action-desc">Balance &amp; history</div>
            </div>
          </a>
        </div>

        @if(auth()->user()->isBuyer() || auth()->user()->isVendor())
        <div class="col-sm-6 col-lg-4">
          <a href="{{ route('jobs.index') }}" class="gasq-action-card">
            <span class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px">
              <i class="fa fa-list"></i>
            </span>
            <div>
              <div class="action-title">My Jobs</div>
              <div class="action-desc">Your postings &amp; bids</div>
            </div>
          </a>
        </div>
        @endif

        <div class="col-sm-6 col-lg-4">
          <a href="{{ route('discovery-call.index') }}" class="gasq-action-card">
            <span class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px">
              <i class="fa fa-phone-alt"></i>
            </span>
            <div>
              <div class="action-title">Discovery Call</div>
              <div class="action-desc">Request a call</div>
            </div>
          </a>
        </div>

        @if(auth()->user()->isAdmin())
        <div class="col-sm-6 col-lg-4">
          <a href="{{ route('admin.settings') }}" class="gasq-action-card">
            <span class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px">
              <i class="fa fa-cog"></i>
            </span>
            <div>
              <div class="action-title">Settings</div>
              <div class="action-desc">Site settings &amp; logo</div>
            </div>
          </a>
        </div>
        @endif

      </div>
    </div>
  </div>

  {{-- Calculator Hub --}}
  <div class="card gasq-card">
    <div class="card-header py-3 px-4">
      <div class="d-flex align-items-center gap-2 mb-1">
        <div class="gasq-icon-badge"><i class="fa fa-calculator fa-sm"></i></div>
        <h2 class="gasq-card-title-lg mb-0">Security Calculators</h2>
      </div>
      <p class="text-gasq-muted small mb-0">Professional security cost analysis and workforce planning tools.</p>
    </div>
    <div class="card-body p-4">

      {{-- Blade Calculators Grid --}}
      <div class="row g-3">
        <div class="col-md-6 col-lg-4">
          <a href="{{ url('/main-menu-calculator') }}" class="gasq-action-card" style="border-color:rgba(6,45,121,0.18);background:rgba(6,45,121,0.03)">
            <div class="gasq-icon-badge" style="width:40px;height:40px;font-size:1rem"><i class="fa fa-th-large"></i></div>
            <div>
              <div class="action-title">Main Menu Calculator</div>
              <div class="action-desc">6 tabs: Security, Manpower, ROI, Bill Rate</div>
            </div>
          </a>
        </div>
        <div class="col-md-6 col-lg-4">
          <a href="{{ url('/security-billing') }}" class="gasq-action-card">
            <div class="gasq-icon-badge" style="width:40px;height:40px;font-size:1rem"><i class="fa fa-file-invoice-dollar"></i></div>
            <div>
              <div class="action-title">Security Billing</div>
              <div class="action-desc">Billing rate analysis &amp; comparison</div>
            </div>
          </a>
        </div>
        <div class="col-md-6 col-lg-4">
          <a href="{{ url('/mobile-patrol-calculator') }}" class="gasq-action-card">
            <div class="gasq-icon-badge" style="width:40px;height:40px;font-size:1rem"><i class="fa fa-car"></i></div>
            <div>
              <div class="action-title">Mobile Patrol</div>
              <div class="action-desc">Route cost &amp; driving analysis</div>
            </div>
          </a>
        </div>
        <div class="col-md-6 col-lg-4">
          <a href="{{ url('/mobile-patrol-comparison') }}" class="gasq-action-card">
            <div class="gasq-icon-badge" style="width:40px;height:40px;font-size:1rem"><i class="fa fa-code-compare"></i></div>
            <div>
              <div class="action-title">Mobile Patrol Comparison</div>
              <div class="action-desc">Side-by-side patrol analysis</div>
            </div>
          </a>
        </div>
        <div class="col-md-6 col-lg-4">
          <a href="{{ url('/budget-calculator') }}" class="gasq-action-card">
            <div class="gasq-icon-badge" style="width:40px;height:40px;font-size:1rem"><i class="fa fa-piggy-bank"></i></div>
            <div>
              <div class="action-title">Budget Calculator</div>
              <div class="action-desc">Annual security budget planning</div>
            </div>
          </a>
        </div>
        <div class="col-md-6 col-lg-4">
          <a href="{{ url('/mobile-patrol-analysis') }}" class="gasq-action-card">
            <div class="gasq-icon-badge" style="width:40px;height:40px;font-size:1rem"><i class="fa fa-map-marked-alt"></i></div>
            <div>
              <div class="action-title">Mobile Patrol Analysis</div>
              <div class="action-desc">Detailed patrol cost breakdown</div>
            </div>
          </a>
        </div>
      </div>

      {{-- Advanced calculator previews (Blade) --}}
      <hr class="my-4" style="border-color:var(--gasq-border)">
      <div class="d-flex align-items-center gap-2 mb-3">
        <div class="gasq-icon-badge gasq-icon-warning"><i class="fa fa-rocket fa-sm"></i></div>
        <div>
          <div class="fw-semibold small">Advanced Calculators</div>
          <div class="text-gasq-muted" style="font-size:0.75rem">Powered by the GASQ calculator engine</div>
        </div>
      </div>
      <div class="row g-3">
        <div class="col-md-6 col-lg-3">
          <a href="{{ url('/gasq-tco-calculator') }}" class="gasq-action-card">
            <div class="gasq-icon-badge" style="width:40px;height:40px;font-size:1rem"><i class="fa fa-chart-area"></i></div>
            <div>
              <div class="action-title">GASQ TCO</div>
              <div class="action-desc">Total cost of ownership</div>
            </div>
          </a>
        </div>
        <div class="col-md-6 col-lg-3">
          <a href="{{ url('/government-contract-calculator') }}" class="gasq-action-card">
            <div class="gasq-icon-badge" style="width:40px;height:40px;font-size:1rem"><i class="fa fa-landmark"></i></div>
            <div>
              <div class="action-title">Gov't Contract</div>
              <div class="action-desc">SCA/PWA compliance calculator</div>
            </div>
          </a>
        </div>
      </div>

    </div>
  </div>

</div>
</div>
@endsection
