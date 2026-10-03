@extends('emails.layouts.gasq-base', ['preheader' => 'Someone has asked for access to a secure GASQ document.'])

@section('content')
    <h2 style="margin:0 0 12px;font-size:20px;color:#0b2447;">Access requested</h2>

    <p style="margin:0 0 16px;">
        {{ $accessRequest->requester_email }} has asked to view
        {{ $document->typeLabel() }} {{ $document->public_id }}.
        They have not been given access.
    </p>

    <p style="margin:0 0 6px;font-size:13px;color:#6b7280;">
        Name: {{ $accessRequest->requester_name ?: 'Not given' }}<br>
        Company: {{ $accessRequest->requester_company ?: 'Not given' }}<br>
        Requested: {{ $accessRequest->created_at->format('F j, Y g:i A') }}
    </p>

    <p style="margin:16px 0 24px;">
        <a href="{{ $adminUrl }}"
           style="display:inline-block;background:#0d6efd;color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:6px;font-weight:bold;">
            Approve or deny
        </a>
    </p>

    <p style="margin:0;font-size:13px;color:#6b7280;">
        Approving issues them their own secure link, which they verify on their own address.
    </p>
@endsection
