{{-- One document: who can open it, what they did, and the controls to stop it.
     Engagement figures are read from the event trail, never from the document,
     so nothing a reader does can move the document's own status. --}}
@extends('layouts.app')
@section('title', $document->public_id)
@section('header_variant', 'dashboard')

@php
  use App\Support\SecureDocument as Flow;
  $minutes = intdiv((int) $engagement['active_seconds'], 60);
  $seconds = (int) $engagement['active_seconds'] % 60;
@endphp

@section('content')
<div class="min-vh-100 py-4 px-3 px-md-4" style="background:var(--gasq-background)">
<div class="container-xl">

  <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
    <div>
      <div class="small text-gasq-muted">{{ $document->typeLabel() }}</div>
      <h1 class="h3 fw-bold mb-1">{{ $document->public_id }}</h1>
      <div class="text-gasq-muted">{{ $document->title }}@if($document->buyer_organization) · {{ $document->buyer_organization }}@endif</div>
    </div>
    <div class="text-end">
      <span class="badge bg-light text-dark border fs-6">{{ Flow::label($document->status) }}</span>
      <div class="small text-gasq-muted mt-1">
        @if($document->currentVersion)Version {{ $document->currentVersion->version_number }} · @endif
        @if($document->expires_at)access until {{ $document->expires_at->format('M j, Y') }}@endif
      </div>
    </div>
  </div>

  @if(session('status'))<div class="alert alert-success py-2">{{ session('status') }}</div>@endif
  @if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif

  @if($document->isRevoked())
    <div class="alert alert-warning py-2">
      Access revoked {{ $document->revoked_at->diffForHumans() }}@if($document->revocation_reason) — {{ $document->revocation_reason }}@endif.
      <form method="POST" action="{{ route('admin.secure-documents.restore', $document) }}" class="d-inline">
        @csrf <button class="btn btn-sm btn-outline-dark ms-2">Restore access</button>
      </form>
    </div>
  @endif

  {{-- ── Engagement, derived from events ───────────────────────────── --}}
  <div class="row g-2 mb-4">
    @foreach([
      ['Viewers', $engagement['viewers'] . ' / ' . $document->recipients->count()],
      ['Sessions', $engagement['sessions']],
      ['Active reading', $minutes . 'm ' . $seconds . 's'],
      ['Stakeholders', $engagement['stakeholders']],
      ['Pages read', $engagement['page_count']
          ? $engagement['pages_read'] . ' / ' . $engagement['page_count']
          : $engagement['pages_read']],
      ['First viewed', $engagement['first_viewed_at'] ? \Illuminate\Support\Carbon::parse($engagement['first_viewed_at'])->format('M j, g:i A') : 'Not yet'],
      ['Last viewed', $engagement['last_viewed_at'] ? \Illuminate\Support\Carbon::parse($engagement['last_viewed_at'])->format('M j, g:i A') : '—'],
    ] as [$label, $value])
      <div class="col-6 col-md-2">
        <div class="card gasq-card h-100"><div class="card-body p-3">
          <div class="text-gasq-muted" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.07em">{{ $label }}</div>
          <div class="fw-bold text-primary" style="font-size:1.05rem">{{ $value }}</div>
        </div></div>
      </div>
    @endforeach
  </div>

  <div class="row g-3">
    {{-- ── Recipients ──────────────────────────────────────────────── --}}
    <div class="col-lg-7">
      <div class="card gasq-card mb-3">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h6 fw-bold mb-0">Authorised viewers</h2>
            @if($document->status !== Flow::SENT)
              <form method="POST" action="{{ route('admin.secure-documents.send', $document) }}">
                @csrf <button class="btn btn-sm btn-primary">Send document</button>
              </form>
            @endif
          </div>

          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead class="small text-gasq-muted"><tr><th>Recipient</th><th>Role</th><th class="text-end">Sessions</th><th>Last viewed</th><th></th></tr></thead>
              <tbody>
              @foreach($document->recipients as $recipient)
                <tr class="{{ $recipient->isRevoked() ? 'opacity-50' : '' }}">
                  <td>
                    <div class="fw-semibold small">{{ $recipient->displayName() }}</div>
                    <div class="text-gasq-muted" style="font-size:.78rem">{{ $recipient->email }}</div>
                  </td>
                  <td class="small">{{ $recipient->isPrimary() ? 'Primary' : 'Stakeholder' }}</td>
                  <td class="text-end small">{{ $recipient->session_count }}</td>
                  <td class="small">{{ $recipient->last_viewed_at?->diffForHumans() ?? 'Not yet' }}</td>
                  <td class="text-end">
                    @unless($recipient->isRevoked())
                      <form method="POST" action="{{ route('admin.secure-documents.recipients.revoke', [$document, $recipient]) }}">
                        @csrf <button class="btn btn-sm btn-link text-danger p-0">Withdraw</button>
                      </form>
                    @else
                      <span class="small text-gasq-muted">Withdrawn</span>
                    @endunless
                  </td>
                </tr>
              @endforeach
              </tbody>
            </table>
          </div>

          <form method="POST" action="{{ route('admin.secure-documents.recipients.add', $document) }}" class="row g-2 mt-3">
            @csrf
            <div class="col-md-5"><input type="email" name="email" class="form-control form-control-sm" placeholder="colleague@company.com" required></div>
            <div class="col-md-4"><input name="name" class="form-control form-control-sm" placeholder="Name (optional)"></div>
            <div class="col-md-3"><button class="btn btn-sm btn-outline-primary w-100">Add viewer</button></div>
          </form>
        </div>
      </div>

      {{-- ── Access requests ───────────────────────────────────────── --}}
      @if($requests->isNotEmpty())
        <div class="card gasq-card mb-3">
          <div class="card-body">
            <h2 class="h6 fw-bold mb-2">Access requests</h2>
            @foreach($requests as $req)
              <div class="d-flex justify-content-between align-items-center border-bottom py-2 flex-wrap gap-2">
                <div>
                  <div class="small fw-semibold">{{ $req->requester_email }}</div>
                  <div class="text-gasq-muted" style="font-size:.78rem">
                    {{ $req->requester_name ?: 'No name given' }} · {{ $req->created_at->diffForHumans() }}
                  </div>
                </div>
                @if($req->isPending())
                  <form method="POST" action="{{ route('admin.secure-documents.requests.decide', $req) }}" class="d-flex gap-1">
                    @csrf
                    <button name="decision" value="approve" class="btn btn-sm btn-primary">Approve</button>
                    <button name="decision" value="deny" class="btn btn-sm btn-outline-secondary">Deny</button>
                  </form>
                @else
                  <span class="badge bg-light text-dark border">{{ ucfirst($req->status) }}</span>
                @endif
              </div>
            @endforeach
          </div>
        </div>
      @endif

      {{-- ── Stop it ───────────────────────────────────────────────── --}}
      @unless($document->isRevoked())
        <div class="card gasq-card">
          <div class="card-body">
            <h2 class="h6 fw-bold mb-2">Revoke access</h2>
            <p class="small text-gasq-muted">Every link stops working on the next request. The history is kept.</p>
            <form method="POST" action="{{ route('admin.secure-documents.revoke', $document) }}" class="row g-2">
              @csrf
              <div class="col-md-8"><input name="reason" class="form-control form-control-sm" placeholder="Reason" required maxlength="200"></div>
              <div class="col-md-4"><button class="btn btn-sm btn-outline-danger w-100">Revoke access</button></div>
            </form>
          </div>
        </div>
      @endunless

        {{-- ── Page analytics (spec 28) ──────────────────────────────── --}}
        <div class="card gasq-card mb-3">
          <div class="card-body">
            <h2 class="h6 fw-bold mb-1">Reading by page</h2>
            <p class="small text-gasq-muted mb-2">
              Active reading time only, totalled across every session.
            </p>
            @if(count($pages))
              @php $busiest = max(array_column($pages, 'active_seconds')) ?: 1; @endphp
              <table class="table table-sm align-middle mb-0">
                <thead><tr>
                  <th>Page</th><th>Active time</th><th>Visits</th><th>Readers</th><th style="width:30%"></th>
                </tr></thead>
                <tbody>
                  @foreach($pages as $row)
                    <tr>
                      <td class="fw-semibold">{{ $row['page'] }}</td>
                      <td>{{ intdiv($row['active_seconds'], 60) }}m {{ $row['active_seconds'] % 60 }}s</td>
                      <td>{{ $row['visits'] }}</td>
                      <td>{{ $row['readers'] }}</td>
                      <td>
                        <div class="progress" style="height:6px" role="img"
                             aria-label="{{ $row['active_seconds'] }} seconds on page {{ $row['page'] }}">
                          <div class="progress-bar" style="width:{{ round($row['active_seconds'] / $busiest * 100) }}%"></div>
                        </div>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            @else
              <p class="small text-gasq-muted mb-0">No pages read yet.</p>
            @endif
          </div>
        </div>
    </div>

    {{-- ── Activity timeline ───────────────────────────────────────── --}}
    <div class="col-lg-5">
      <div class="card gasq-card">
        <div class="card-body">
          <h2 class="h6 fw-bold mb-3">Activity</h2>
          <div style="max-height:32rem;overflow-y:auto">
            @forelse($events as $event)
              <div class="d-flex gap-2 pb-2 mb-2 border-bottom">
                <div class="small text-gasq-muted" style="min-width:6.5rem">{{ $event->created_at->format('M j, g:i A') }}</div>
                <div>
                  <div class="small fw-semibold">{{ \Illuminate\Support\Str::headline(strtolower($event->event_type)) }}</div>
                  @if($event->recipient)
                    <div class="text-gasq-muted" style="font-size:.78rem">{{ $event->recipient->email }}</div>
                  @endif
                </div>
              </div>
            @empty
              <p class="small text-gasq-muted mb-0">Nothing yet.</p>
            @endforelse
          </div>
        </div>
      </div>
    </div>
  </div>

</div>
</div>
@endsection
