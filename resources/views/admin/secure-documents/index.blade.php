@extends('layouts.app')
@section('title', 'Secure Documents')
@section('header_variant', 'dashboard')

@php use App\Support\SecureDocument as Flow; @endphp

@section('content')
<div class="min-vh-100 py-4 px-3 px-md-4" style="background:var(--gasq-background)">
<div class="container-xl">

  <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-0">Secure Documents</h1>
      <div class="text-gasq-muted small">
        Delivered by recipient-specific link, never as an attachment.
        @if($pendingRequests > 0)
          <span class="badge bg-warning text-dark ms-1">{{ $pendingRequests }} access request{{ $pendingRequests === 1 ? '' : 's' }} waiting</span>
        @endif
      </div>
    </div>
    <a href="{{ route('admin.secure-documents.create') }}" class="btn btn-primary"><i class="fa fa-plus me-1"></i> New document</a>
  </div>

  @if(session('status'))<div class="alert alert-success py-2">{{ session('status') }}</div>@endif

  <form method="GET" class="row g-2 mb-3">
    <div class="col-md-4"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Number, title, buyer or recipient email"></div>
    <div class="col-md-3">
      <select name="status" class="form-select">
        <option value="">Any status</option>
        @foreach([Flow::DRAFT, Flow::READY, Flow::SENT, Flow::EXPIRED, Flow::REVOKED, Flow::SUPERSEDED] as $s)
          <option value="{{ $s }}" @selected(request('status') === $s)>{{ Flow::label($s) }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-3">
      <select name="type" class="form-select">
        <option value="">Any type</option>
        @foreach(Flow::types() as $key => $meta)
          <option value="{{ $key }}" @selected(request('type') === $key)>{{ $meta['label'] }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filter</button></div>
  </form>

  <div class="card gasq-card">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead class="small text-gasq-muted">
          <tr>
            <th>Document</th><th>Buyer</th><th>Type</th><th>Status</th>
            <th class="text-end">Viewers</th><th class="text-end">Sessions</th><th>Last viewed</th><th>Expires</th><th></th>
          </tr>
        </thead>
        <tbody>
        @forelse($documents as $doc)
          @php $viewers = $doc->recipients->where('session_count', '>', 0); @endphp
          <tr>
            <td>
              <a href="{{ route('admin.secure-documents.show', $doc) }}" class="fw-semibold text-decoration-none">{{ $doc->public_id }}</a>
              <div class="small text-gasq-muted">{{ \Illuminate\Support\Str::limit($doc->title, 44) }}</div>
            </td>
            <td class="small">{{ $doc->buyer_organization ?: '—' }}</td>
            <td class="small">{{ $doc->typeLabel() }}</td>
            <td><span class="badge bg-light text-dark border">{{ Flow::label($doc->status) }}</span></td>
            <td class="text-end">{{ $viewers->count() }} / {{ $doc->recipients->count() }}</td>
            <td class="text-end">{{ $doc->recipients->sum('session_count') }}</td>
            <td class="small">{{ optional($doc->recipients->max('last_viewed_at'))?->diffForHumans() ?? 'Not yet' }}</td>
            <td class="small">{{ $doc->expires_at?->format('M j, Y') ?? '—' }}</td>
            <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.secure-documents.show', $doc) }}">Open</a></td>
          </tr>
        @empty
          <tr><td colspan="9" class="text-center text-gasq-muted py-4">No documents yet.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3">{{ $documents->links() }}</div>
</div>
</div>
@endsection
