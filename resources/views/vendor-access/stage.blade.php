{{-- One stage of the GASQ Vendor Access sequence. Stages 1-3 are built;
     the rest show what is coming and stay locked until their turn. --}}
@extends('layouts.app')
@section('title', 'Vendor Access')
@section('header_variant', 'dashboard')

@php use App\Support\VendorEngagement as Flow; @endphp

@push('styles')
<style>
  .va-wrap { max-width: 68rem; margin: 0 auto; }
  .va-rail { display: flex; flex-wrap: wrap; gap: .4rem; margin-bottom: 1.25rem; }
  .va-step { display: inline-flex; align-items: center; gap: .45rem; padding: .3rem .7rem; border-radius: 999px;
             background: rgba(15,23,42,.05); font-size: .78rem; color: var(--gasq-muted); white-space: nowrap; }
  .va-step .n { display: inline-flex; align-items: center; justify-content: center; width: 1.25rem; height: 1.25rem;
                border-radius: 50%; background: rgba(15,23,42,.12); font-size: .68rem; font-weight: 700; }
  .va-step a { color: inherit; text-decoration: none; }
  .va-step.done { background: rgba(22,163,74,.12); color: #15803d; }
  .va-step.done .n { background: #15803d; color: #fff; }
  .va-step.now { background: var(--gasq-primary); color: #fff; font-weight: 600; }
  .va-step.now .n { background: rgba(255,255,255,.25); color: #fff; }
  .va-step.open { background: rgba(6,45,121,.1); color: var(--gasq-primary); }
  .va-step.locked { opacity: .55; }
  .va-kicker { font-size: .72rem; text-transform: uppercase; letter-spacing: .12em; color: var(--gasq-muted); }
  .va-panel { border: 1px solid rgba(15,23,42,.08); border-radius: 1rem; background: #fff; padding: 1.25rem 1.4rem; }
  .va-facts { display: grid; grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr)); gap: .75rem; }
  .va-fact { border: 1px solid rgba(6,45,121,.1); border-radius: .75rem; padding: .7rem .9rem; }
  .va-fact .l { font-size: .7rem; text-transform: uppercase; letter-spacing: .07em; color: var(--gasq-muted); }
  .va-fact .v { font-size: 1.05rem; font-weight: 700; color: var(--gasq-primary); }
  .va-note { border-left: 3px solid var(--gasq-primary); background: rgba(6,45,121,.05);
             padding: .7rem .9rem; border-radius: 0 .6rem .6rem 0; font-size: .88rem; }
  .va-note.warn { border-color: #d97706; background: rgba(217,119,6,.08); }
  .va-soon { border: 1px dashed rgba(15,23,42,.18); border-radius: 1rem; padding: 1.25rem 1.4rem; color: var(--gasq-muted); }
</style>
@endpush

@section('content')
<div class="min-vh-100 py-4 px-3 px-md-4" style="background:var(--gasq-background)">
  <div class="va-wrap">

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
      <div>
        <div class="va-kicker">Private opportunity · invitation {{ $invitation->invite_key ? substr($invitation->invite_key, 0, 8) : $invitation->id }}</div>
        <h1 class="h3 fw-bold mb-0">{{ $job?->title ?: 'Qualified Security Opportunity' }}</h1>
        <div class="text-gasq-muted small">{{ $job?->location ?: 'Location shared after acceptance' }}</div>
      </div>
      <div class="text-end small text-gasq-muted">
        Stage {{ Flow::position($stage) }} of {{ count(Flow::keys()) }}<br>
        <span class="fw-semibold">{{ Flow::label($stage) }}</span>
      </div>
    </div>

    @include('vendor-access._rail')

    @if(session('status'))<div class="alert alert-success py-2">{{ session('status') }}</div>@endif
    @if(session('info'))<div class="alert alert-light border py-2">{{ session('info') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif

    @if($engagement->status === Flow::STATUS_DECLINED)
      <div class="va-note warn mb-3">
        You declined this opportunity on {{ $engagement->declined_at?->format('F j, Y') }}. GASQ has the reason on file.
      </div>
    @elseif($engagement->status === Flow::STATUS_ADJUSTMENT_REQUESTED)
      <div class="va-note warn mb-3">
        Scope adjustment requested on {{ $engagement->adjustment_requested_at?->format('F j, Y') }}. Pricing stays closed until GASQ answers.
      </div>
    @endif

    <div class="va-panel">
      <div class="va-kicker mb-1">{{ Flow::label($stage) }}</div>
      <p class="text-gasq-muted mb-3">{{ Flow::stages()[$stage]['blurb'] }}</p>

      {{-- ── Stage 1 · Opportunity ───────────────────────────────── --}}
      @if($stage === Flow::OPPORTUNITY)
        <div class="va-facts mb-3">
          <div class="va-fact"><div class="l">Service</div><div class="v">{{ $job?->service_type ?: 'Security officer' }}</div></div>
          <div class="va-fact"><div class="l">Weekly hours</div><div class="v">{{ $job?->weekly_hours ? number_format((float) $job->weekly_hours) : '—' }}</div></div>
          <div class="va-fact"><div class="l">Start date</div><div class="v">{{ $job?->start_date ? \Illuminate\Support\Carbon::parse($job->start_date)->format('M j, Y') : 'To confirm' }}</div></div>
          <div class="va-fact"><div class="l">Buyer qualified</div><div class="v">{{ $opportunity?->decision_maker_verified ? 'Yes' : 'In review' }}</div></div>
        </div>

        <div class="va-note mb-3">
          GASQ qualifies the buyer before you see this: decision maker, funding and timeline.
          You will not be asked for a price until the buyer has met you and seen your operating solution.
        </div>

        @if($engagement->stageRecord(Flow::OPPORTUNITY)?->isComplete())
          <p class="small text-success mb-0"><strong>Reviewed.</strong> Continue to Accept / Decline.</p>
        @else
          <form method="POST" action="{{ route('vendor-access.reviewed', $invitation) }}">
            @csrf
            <button class="btn btn-primary">I have reviewed this opportunity</button>
          </form>
        @endif

      {{-- ── Stage 2 · Accept / Decline / Adjust ─────────────────── --}}
      @elseif($stage === Flow::RESPONSE)
        @if($engagement->accepted_at)
          <p class="small text-success mb-0"><strong>Accepted</strong> on {{ $engagement->accepted_at->format('F j, Y') }}.</p>
        @elseif($engagement->declined_at)
          <p class="small text-gasq-muted mb-0">Declined on {{ $engagement->declined_at->format('F j, Y') }}.</p>
        @else
          <div class="row g-3">
            <div class="col-lg-4">
              <form method="POST" action="{{ route('vendor-access.accept', $invitation) }}" class="h-100">
                @csrf
                <div class="border rounded-3 p-3 h-100 d-flex flex-column">
                  <h3 class="h6 fw-bold">Accept</h3>
                  <p class="small text-gasq-muted flex-grow-1">Take the opportunity forward. Qualification opens next.</p>
                  <button class="btn btn-primary w-100">Accept opportunity</button>
                </div>
              </form>
            </div>

            <div class="col-lg-4">
              <form method="POST" action="{{ route('vendor-access.request-adjustment', $invitation) }}" class="h-100">
                @csrf
                <div class="border rounded-3 p-3 h-100 d-flex flex-column">
                  <h3 class="h6 fw-bold">Request scope adjustment</h3>
                  <p class="small text-gasq-muted mb-2">Changes what would be priced: hours, posts, wage, service type, equipment or start date.</p>
                  <textarea name="note" class="form-control form-control-sm mb-2" rows="3" required minlength="10"
                            placeholder="What needs to change, and why"></textarea>
                  <button class="btn btn-outline-primary w-100 mt-auto">Request adjustment</button>
                </div>
              </form>
            </div>

            <div class="col-lg-4">
              <form method="POST" action="{{ route('vendor-access.decline', $invitation) }}" class="h-100">
                @csrf
                <div class="border rounded-3 p-3 h-100 d-flex flex-column">
                  <h3 class="h6 fw-bold">Decline</h3>
                  <p class="small text-gasq-muted mb-2">Tell us why so we stop sending work that does not fit.</p>
                  <select name="reason" class="form-select form-select-sm mb-2" required>
                    <option value="">Select a reason…</option>
                    @foreach(Flow::declineReasons() as $key => $label)
                      <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                  </select>
                  <textarea name="note" class="form-control form-control-sm mb-2" rows="2" placeholder="Anything else (optional)"></textarea>
                  <button class="btn btn-outline-secondary w-100 mt-auto">Decline</button>
                </div>
              </form>
            </div>
          </div>

          <div class="va-note mt-3">
            A question that does not change what is being priced is a clarification, not a scope adjustment.
            Send those to GASQ directly and keep this engagement moving.
          </div>
        @endif

      {{-- ── Stage 3 · Qualify ───────────────────────────────────── --}}
      @elseif($stage === Flow::QUALIFY)
        <p class="small text-gasq-muted">GASQ holds these on file for your company. Confirm they are current for this contract.</p>
        <ul class="small text-gasq-muted">
          <li>State security licence and business licence</li>
          <li>Certificate of insurance, general liability and workers' compensation</li>
          <li>W-9 and capability statement</li>
        </ul>

        @if($engagement->stageRecord(Flow::QUALIFY)?->isComplete())
          <p class="small text-success mb-0"><strong>Qualification confirmed.</strong></p>
        @else
          <form method="POST" action="{{ route('vendor-access.qualify', $invitation) }}">
            @csrf
            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" name="confirm" id="va_confirm" value="1" required>
              <label class="form-check-label small" for="va_confirm">
                I confirm these documents are current and accurate for this opportunity.
              </label>
            </div>
            <button class="btn btn-primary">Confirm qualification</button>
            <a href="{{ route('vendor-questionnaires.index') ?? '#' }}" class="btn btn-link btn-sm">Update documents</a>
          </form>
        @endif

      {{-- ── Stages 4-10 · not built yet ─────────────────────────── --}}
      @else
        <div class="va-soon">
          <p class="mb-1"><strong>{{ Flow::label($stage) }}</strong> is part of the sequence but is not open in this release.</p>
          <p class="mb-0 small">
            Meeting, assessment, interview and operating solution come next, then the buyer's selection.
            Price is submitted sealed and stays closed until the authorised reveal.
          </p>
        </div>
      @endif
    </div>

    <p class="small text-gasq-muted mt-3 mb-0">
      Every action here is recorded with the time and account that took it. This invitation is issued to
      {{ $invitation->vendor?->company ?: $invitation->vendor?->name }} and is not transferable.
    </p>

  </div>
</div>
@endsection
