@extends('layouts.app')
@section('title', 'New Secure Document')
@section('header_variant', 'dashboard')

@php use App\Support\SecureDocument as Flow; @endphp

@section('content')
<div class="min-vh-100 py-4 px-3" style="background:var(--gasq-background)">
<div class="container" style="max-width:56rem">

  <h1 class="h3 fw-bold mb-1">New secure document</h1>
  <p class="text-gasq-muted small mb-4">The file is stored privately and delivered as a link. It is never attached to an email.</p>

  @if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif

  <form method="POST" action="{{ route('admin.secure-documents.store') }}" enctype="multipart/form-data" class="card gasq-card">
    @csrf
    <div class="card-body p-4">

      <h2 class="h6 fw-bold mb-3">Document</h2>
      <div class="row g-3 mb-4">
        <div class="col-md-7">
          <label class="form-label small fw-medium">Title</label>
          <input name="title" class="form-control" value="{{ old('title') }}" required maxlength="200">
        </div>
        <div class="col-md-5">
          <label class="form-label small fw-medium">Type</label>
          <select name="document_type" class="form-select" required>
            @foreach(Flow::types() as $key => $meta)
              <option value="{{ $key }}" @selected(old('document_type') === $key)>{{ $meta['label'] }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-7">
          <label class="form-label small fw-medium">Buyer organisation</label>
          <input name="buyer_organization" class="form-control" value="{{ old('buyer_organization') }}" maxlength="160">
        </div>
        <div class="col-md-5">
          <label class="form-label small fw-medium">PDF</label>
          <input type="file" name="file" class="form-control" accept="application/pdf" required>
        </div>
      </div>

      <h2 class="h6 fw-bold mb-3">Recipient</h2>
      <div class="row g-3 mb-4">
        <div class="col-md-7">
          <label class="form-label small fw-medium">Email</label>
          <input type="email" name="recipient_email" class="form-control" value="{{ old('recipient_email') }}" required>
          <div class="form-text">Their verification code goes to this address, and only this address.</div>
        </div>
        <div class="col-md-5">
          <label class="form-label small fw-medium">Name</label>
          <input name="recipient_name" class="form-control" value="{{ old('recipient_name') }}" maxlength="120">
        </div>
      </div>

      <h2 class="h6 fw-bold mb-3">Security</h2>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label small fw-medium">Level</label>
          <select name="security_level" class="form-select">
            @foreach(Flow::securityLevels() as $key => $label)
              <option value="{{ $key }}" @selected((old('security_level') ?? Flow::LEVEL_OTP) === $key)>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-medium">Additional viewers</label>
          <select name="stakeholder_policy" class="form-select">
            @foreach(Flow::stakeholderPolicies() as $key => $label)
              <option value="{{ $key }}" @selected((old('stakeholder_policy') ?? Flow::POLICY_GASQ_APPROVAL) === $key)>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-medium">Access expires in (days)</label>
          <input type="number" name="expires_in_days" class="form-control" value="{{ old('expires_in_days', 30) }}" min="1" max="365">
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-medium">Document password (optional)</label>
          <input type="text" name="password" class="form-control" value="{{ old('password') }}" minlength="6" maxlength="100">
          <div class="form-text">Share it separately from the link.</div>
        </div>
        <div class="col-md-4 d-flex flex-column justify-content-end pb-1">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="allow_download" value="1" id="dl" @checked(old('allow_download'))>
            <label class="form-check-label small" for="dl">Allow download</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="allow_print" value="1" id="pr" @checked(old('allow_print'))>
            <label class="form-check-label small" for="pr">Allow printing</label>
          </div>
        </div>
      </div>
    </div>

    <div class="card-footer bg-white d-flex gap-2 justify-content-end p-3">
      <button name="send_now" value="0" class="btn btn-outline-secondary">Save without sending</button>
      <button name="send_now" value="1" class="btn btn-primary">Create and send</button>
    </div>
  </form>
</div>
</div>
@endsection
