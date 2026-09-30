@extends('secure-documents._shell', ['title' => 'Document unavailable'])

@section('content')
<div class="sd-card">
    <h1>
        @switch($reason)
            @case('expired') This document is no longer available @break
            @case('revoked') This document is no longer available @break
            @case('recipient_revoked') Your access has been withdrawn @break
            @case('not_sent') This document has not been issued yet @break
            @default We can't open this link
        @endswitch
    </h1>
    <p class="sd-note" style="margin-bottom:20px;">{{ $message }}</p>
    <a class="sd-btn" href="{{ route('contact') }}">Contact GASQ</a>
</div>
@endsection
