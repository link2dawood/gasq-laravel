{{-- Identity check (spec 11). The address is the one the document was issued
     to, never one typed by whoever is holding the link. --}}
@extends('secure-documents._shell', ['title' => 'Verify your identity'])

@section('content')
<div class="sd-card">
    <div class="sd-kicker">{{ $document->public_id }}</div>
    <h1>Verify your identity</h1>

    <p>{{ $document->typeLabel() }}@if($document->buyer_organization) prepared for {{ $document->buyer_organization }}@endif.</p>

    @if(session('status') || $errors->any())
        <form method="POST" action="{{ route('secure-documents.verify') }}">
            @csrf
            <label for="code" style="display:block;font-weight:600;margin:14px 0 8px;">Six-digit code</label>
            <input id="code" name="code" class="sd-field sd-code" inputmode="numeric" autocomplete="one-time-code"
                   maxlength="6" pattern="[0-9]*" required autofocus>

            @if($document->requiresPassword())
                <label for="password" style="display:block;font-weight:600;margin:14px 0 8px;">Document password</label>
                <input id="password" name="password" type="password" class="sd-field" required>
                <p class="sd-note" style="margin:8px 0 0;">GASQ shares this separately from the link.</p>
            @endif

            <p class="sd-note" style="margin:12px 0 18px;">Sent to {{ $maskedEmail }} · expires in 10 minutes.</p>
            <button type="submit" class="sd-btn">Open document</button>
        </form>

        <form method="POST" action="{{ route('secure-documents.send-code') }}" style="margin-top:14px;">
            @csrf
            <button type="submit" class="sd-btn secondary">Send a new code</button>
        </form>
    @else
        <p class="sd-note" style="margin:14px 0 18px;">
            We'll email a six-digit code to {{ $maskedEmail }} to confirm it's you.
        </p>
        <form method="POST" action="{{ route('secure-documents.send-code') }}">
            @csrf
            <button type="submit" class="sd-btn">Email my code</button>
        </form>
    @endif
</div>

{{-- The forwarded-link path (spec 31). --}}
<div class="sd-card" style="margin-top:16px;">
    <h1 style="font-size:1.05rem;margin-bottom:.6rem;">Not {{ $maskedEmail }}?</h1>
    <p class="sd-note" style="margin-bottom:14px;">
        This document was issued to another recipient. Enter your business email to request your own access.
        You will not inherit anyone else's.
    </p>
    <form method="POST" action="{{ route('secure-documents.request-access', session('secure_document_token')) }}">
        @csrf
        <input name="email" type="email" class="sd-field" placeholder="you@company.com" required style="margin-bottom:8px;">
        <input name="name" class="sd-field" placeholder="Your name (optional)" style="margin-bottom:8px;">
        <button type="submit" class="sd-btn secondary">Request access</button>
    </form>
</div>
@endsection
