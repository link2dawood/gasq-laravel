{{-- Expired, revoked or unknown invitation (spec 3). --}}
@extends('private-estimates.buyer._shell', ['title' => 'Invitation Unavailable'])

@section('content')
    <div class="pe-card">
        <h1>
            @switch($reason)
                @case('expired') This Private Estimate Invitation Has Expired @break
                @case('revoked') This Private Estimate Invitation Was Withdrawn @break
                @default This Private Estimate Invitation Is Not Available
            @endswitch
        </h1>

        <p class="pe-meta" style="margin-bottom:22px;">
            @if($reason === 'expired')
                Invitations are time limited. Ask the vendor who invited you for a new one and it will arrive at the same address.
            @elseif($reason === 'revoked')
                The vendor withdrew this invitation. If you think that's a mistake, contact them directly.
            @else
                This link doesn't match an active invitation. Check that you used the most recent email.
            @endif
        </p>

        <a class="pe-btn" href="{{ route('contact') }}">Request New Invitation</a>
    </div>
@endsection
