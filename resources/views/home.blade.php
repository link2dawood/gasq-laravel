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

  {{-- Buyer Stats Row --}}
  @php
      $tierBadgeClass = match (strtolower((string) ($buyerLatestTier ?? ''))) {
          'a' => 'badge bg-success',
          'b' => 'badge bg-warning text-dark',
          'c' => 'badge bg-danger',
          default => 'badge bg-secondary',
      };
      $tierLabel = match (strtolower((string) ($buyerLatestTier ?? ''))) {
          'a' => 'Tier A — Active',
          'b' => 'Tier B — Under Review',
          'c' => 'Tier C — Pending Qualification',
          default => 'No active jobs',
      };
  @endphp
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="gasq-stat-card">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <div class="gasq-icon-badge"><i class="fa fa-briefcase fa-sm"></i></div>
          <span class="badge rounded-pill" style="background:rgba(6,45,121,0.08);color:var(--gasq-primary);font-size:0.7rem;font-weight:500">Active</span>
        </div>
        <div class="stat-value">{{ ($buyerActiveJobs ?? collect())->count() }}</div>
        <div class="stat-sub">Active jobs</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="gasq-stat-card">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <div class="gasq-icon-badge"><i class="fa fa-handshake fa-sm"></i></div>
          <span class="badge rounded-pill" style="background:rgba(6,45,121,0.08);color:var(--gasq-primary);font-size:0.7rem;font-weight:500">Pipeline</span>
        </div>
        <div class="stat-value">{{ $buyerVendorsAccepted ?? 0 }}</div>
        <div class="stat-sub">Vendor acceptances</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="gasq-stat-card">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <div class="gasq-icon-badge"><i class="fa fa-file-invoice-dollar fa-sm"></i></div>
          <span class="badge rounded-pill" style="background:rgba(6,45,121,0.08);color:var(--gasq-primary);font-size:0.7rem;font-weight:500">Bids</span>
        </div>
        <div class="stat-value">{{ $buyerBidsReceived ?? 0 }}</div>
        <div class="stat-sub">Bids received</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="gasq-stat-card">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <div class="gasq-icon-badge"><i class="fa fa-wallet fa-sm"></i></div>
          <span class="badge rounded-pill" style="background:rgba(6,45,121,0.08);color:var(--gasq-primary);font-size:0.7rem;font-weight:500">Balance</span>
        </div>
        <div class="stat-value gasq-mono">{{ $buyerWalletBalance ?? ($walletBalance ?? 0) }}</div>
        <div class="stat-sub">Credits available</div>
      </div>
    </div>
  </div>

  {{-- Next Action + Qualification Status --}}
  @if(isset($buyerNextAction) && is_array($buyerNextAction))
    @php
      $bg = match($buyerNextAction['tone']) {
          'warn' => 'background:#fff3cd;border:1px solid #ffe69c;color:#664d03;',
          'success' => 'background:#d1e7dd;border:1px solid #b5dfc4;color:#0a3622;',
          'info' => 'background:#cfe2ff;border:1px solid #b6d4fe;color:#084298;',
          default => 'background:#f8f9fa;border:1px solid #dee2e6;color:#212529;',
      };
    @endphp
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 p-3 rounded mb-4" style="{{ $bg }}">
      <div>
        <div class="fw-semibold mb-1">Next action</div>
        <div>{{ $buyerNextAction['message'] }}</div>
      </div>
      <div class="d-flex align-items-center gap-3">
        <span class="{{ $tierBadgeClass }}">{{ $tierLabel }}</span>
        <a href="{{ $buyerNextAction['href'] }}" class="btn btn-primary btn-sm">{{ $buyerNextAction['cta'] }}</a>
      </div>
    </div>
  @endif

  {{-- Active Job Offer Summary cards (unredacted) + status checklist + hired vendor picker --}}
  @if(isset($buyerActiveJobs) && $buyerActiveJobs->isNotEmpty())
    @foreach($buyerActiveJobs as $job)
      <div class="mb-4">
        @include('partials.lead-summary', [
            'job' => $job,
            'opportunity' => $job->vendorOpportunity,
            'redacted' => false,
            'showScope' => true,
        ])

        @php
            $acceptedInvites = $job->vendorOpportunity?->invitations
                ?->whereIn('status', ['accepted', 'bid_submitted']) ?? collect();
        @endphp

        <div class="card border-secondary mb-3">
          <div class="card-header bg-light fw-bold">Job Offer Status</div>
          <div class="card-body">
            <form method="POST" action="{{ route('jobs.workflow-status', $job) }}">
              @csrf
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Offer status</label>
                  <select name="offer_status" class="form-select form-select-sm">
                    <option value="open" @selected($job->offer_status === 'open')>Open</option>
                    <option value="hired" @selected($job->offer_status === 'hired')>Close — I hired someone</option>
                    <option value="closed_no_hire" @selected($job->offer_status === 'closed_no_hire')>Close — I did not hire anyone</option>
                  </select>
                </div>

                @foreach([
                    'interviews_scheduled' => 'Interviews scheduled?',
                    'interviews_completed' => 'All vendors who accepted the job offer were interviewed?',
                    'risk_assessment_scheduled' => 'On-site Risk Assessment scheduled?',
                    'risk_assessment_completed' => 'On-site Risk Assessment conducted?',
                    'final_verifications_complete' => 'All required insurances, licenses and certifications verified before final offer?',
                ] as $field => $label)
                  <div class="col-md-6">
                    <label class="form-label fw-semibold small">{{ $label }}</label>
                    <select name="{{ $field }}" class="form-select form-select-sm">
                      <option value="">—</option>
                      <option value="yes" @selected($job->{$field} === true)>Yes</option>
                      <option value="no" @selected($job->{$field} === false)>No</option>
                    </select>
                  </div>
                @endforeach
              </div>

              @if($acceptedInvites->isNotEmpty())
                <hr class="my-3">
                <div>
                  <div class="fw-semibold mb-2">Vendors that accepted the job offer:</div>
                  <ul class="mb-3">
                    @foreach($acceptedInvites as $inv)
                      <li>{{ $inv->vendor?->name }}{{ $inv->vendor?->company ? ' — ' . $inv->vendor->company : '' }}</li>
                    @endforeach
                  </ul>

                  <label class="form-label fw-semibold">Which vendor did you hire?</label>
                  <div class="d-flex flex-column gap-2">
                    @foreach($acceptedInvites as $inv)
                      @php $bidId = $inv->bid?->id; @endphp
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="hired_bid_id" id="bid_{{ $inv->id }}" value="{{ $bidId }}" @disabled(! $bidId) @checked($job->hired_bid_id === $bidId)>
                        <label class="form-check-label" for="bid_{{ $inv->id }}">
                          {{ $inv->vendor?->name }}{{ $inv->vendor?->company ? ' — ' . $inv->vendor->company : '' }}
                        </label>
                      </div>
                    @endforeach
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="hired_bid_id" id="bid_none_{{ $job->id }}" value="" @checked(! $job->hired_bid_id)>
                      <label class="form-check-label" for="bid_none_{{ $job->id }}">I didn't hire anyone yet</label>
                    </div>
                  </div>
                </div>
              @endif

              <div class="d-flex justify-content-between mt-3">
                <a href="{{ route('jobs.show', $job) }}" class="btn btn-link btn-sm text-decoration-none">Open job details</a>
                <button type="submit" class="btn btn-primary btn-sm">Save status</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    @endforeach

    <div class="d-flex justify-content-between align-items-center mb-4">
      <a href="{{ route('jobs.index') }}" class="btn btn-outline-secondary btn-sm">View all my jobs</a>
      <a href="{{ route('post-job.index') }}" class="btn btn-primary">
        <i class="fa fa-plus me-1"></i> Submit another job offer
      </a>
    </div>
  @else
    <div class="alert alert-info d-flex justify-content-between align-items-center">
      <span>No active job offers yet. Submit one to receive qualified vendor responses.</span>
      <a href="{{ route('post-job.index') }}" class="btn btn-primary btn-sm">Submit a job offer</a>
    </div>
  @endif

  {{-- GASQ Protections --}}
  <div class="card gasq-card mb-4" style="background:#f0f9ff;border:1px solid #bae6fd;">
    <div class="card-body py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h3 class="h6 mb-1" style="color:#0369a1;"><i class="fa fa-shield-halved me-2"></i>Your GASQ Protections</h3>
        <div class="text-gasq-muted small mb-0">
          <strong>Price Lock Guarantee</strong> · <strong>Vendor Replacement Guarantee</strong> · Backed across every accepted engagement.
        </div>
      </div>
      <a href="{{ route('faq') }}" class="btn btn-outline-primary btn-sm">Learn more</a>
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
          <a href="{{ url('/budget-calculator') }}" class="gasq-action-card">
            <div class="gasq-icon-badge" style="width:40px;height:40px;font-size:1rem"><i class="fa fa-piggy-bank"></i></div>
            <div>
              <div class="action-title">Workforce Calculator</div>
              <div class="action-desc">Annual workforce planning</div>
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
