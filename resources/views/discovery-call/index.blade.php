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
            {{-- Live booking calendar (Bookafy). The iframe is tall enough to
                 show the whole widget without an inner scrollbar; the script
                 below also grows it to fit if Bookafy reports its height. --}}
            <iframe id="bookafyFrame"
                    src="https://getasecurityquote.bookafy.com/"
                    title="Book a Discovery Call with GASQ"
                    style="width:100%; min-height:1400px; height:1400px; border:0; display:block;"
                    scrolling="no"
                    loading="lazy"
                    allow="fullscreen"></iframe>
        </div>
    </div>

    <p class="text-center text-gasq-muted small mt-3 mb-0">
        Trouble loading the calendar?
        <a href="https://getasecurityquote.bookafy.com/" target="_blank" rel="noopener">Open the booking page in a new tab</a>.
    </p>
</div>

@push('scripts')
<script>
    // Best-effort: if the Bookafy embed posts its content height, grow the
    // iframe to match so the widget shows in full with no inner scrolling.
    (function () {
        var frame = document.getElementById('bookafyFrame');
        if (!frame) return;

        window.addEventListener('message', function (event) {
            if (typeof event.origin !== 'string' || event.origin.indexOf('bookafy.com') === -1) {
                return;
            }

            var data = event.data;
            var height = null;

            if (typeof data === 'number') {
                height = data;
            } else if (data && typeof data === 'object') {
                height = data.height || data.frameHeight || (data.data && data.data.height) || null;
            } else if (typeof data === 'string') {
                var match = data.match(/(\d{3,5})/);
                if (match) { height = parseInt(match[1], 10); }
            }

            if (height && height > 400 && height < 6000) {
                frame.style.height = height + 'px';
                frame.style.minHeight = height + 'px';
            }
        }, false);
    })();
</script>
@endpush
@endsection

