@extends('emails.layouts.gasq-base', ['preheader' => 'A secure GASQ document is ready for you to review.'])

@section('content')
    <h2 style="margin:0 0 12px;font-size:20px;color:#0b2447;">{{ $document->typeLabel() }}</h2>

    <p style="margin:0 0 16px;">
        {{ $recipient->name ? $recipient->name . ',' : 'Hello,' }} your {{ strtolower($document->typeLabel()) }}
        is ready to review.
    </p>

    <p style="margin:0 0 24px;">
        <a href="{{ $url }}"
           style="display:inline-block;background:#0d6efd;color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:6px;font-weight:bold;">
            View Secure Document
        </a>
    </p>

    <p style="margin:0 0 6px;font-size:13px;color:#6b7280;">Document {{ $document->public_id }}</p>
    @if($document->expires_at)
        <p style="margin:0 0 6px;font-size:13px;color:#6b7280;">Access expires {{ $document->expires_at->format('F j, Y') }}.</p>
    @endif
    <p style="margin:0;font-size:13px;color:#6b7280;">
        This link was prepared for {{ $recipient->email }}. We'll email a six-digit code to confirm it's you.
        The document is confidential and is not to be forwarded; colleagues can request their own access.
    </p>
@endsection
