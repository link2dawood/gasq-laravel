@extends('secure-documents._shell', ['title' => 'Access request'])

@section('content')
<div class="sd-card">
    @if($outcome === 'granted')
        <h1>You already have access</h1>
        <p class="sd-note">{{ $email }} is authorised for this document. Open your own link, or ask GASQ to resend it.</p>
    @elseif($outcome === 'closed')
        <h1>Access request not available</h1>
        <p class="sd-note">This document is limited to its named recipients. Contact GASQ if you need a copy.</p>
    @else
        <h1>Access request submitted</h1>
        <p class="sd-note">
            GASQ has been notified and will decide whether to grant {{ $email }} access to
            {{ $document->public_id }}. You'll receive your own secure link if it's approved.
        </p>
    @endif
    <p style="margin-top:18px;"><a class="sd-btn secondary" href="{{ route('contact') }}">Contact GASQ</a></p>
</div>
@endsection
