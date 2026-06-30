@extends('layouts.app')

@section('title', 'Schedule a Discovery Call')

@section('content')
<div class="container py-4">
    <h1 class="h2 mb-2">Schedule a Discovery Call</h1>
    <p class="text-gasq-muted mb-4">
        Pick a time that works for you and we&rsquo;ll talk through your property, current security setup, and goals.
    </p>

    <div class="card gasq-card shadow-sm">
        <div class="card-body p-0">
            {{-- Live booking calendar (Bookafy). If the embed is blocked,
                 use the "open in a new tab" link below. --}}
            <iframe src="https://getasecurityquote.bookafy.com/"
                    title="Book a Discovery Call with GASQ"
                    style="width:100%; min-height:840px; border:0; display:block;"
                    loading="lazy"
                    allow="fullscreen"></iframe>
        </div>
    </div>

    <p class="text-center text-gasq-muted small mt-3 mb-0">
        Trouble loading the calendar?
        <a href="https://getasecurityquote.bookafy.com/" target="_blank" rel="noopener">Open the booking page in a new tab</a>.
    </p>
</div>
@endsection

