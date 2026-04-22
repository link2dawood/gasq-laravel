@extends('layouts.app')

@section('title', 'Twilio Health Check')

@section('content')
<div class="container py-4">
    <div class="mb-4">
        <h1 class="h2 fw-bold mb-1">Twilio Health Check</h1>
        <p class="text-gasq-muted mb-0">Verify the live Twilio configuration Laravel is using and send a controlled test SMS.</p>
    </div>

    <div class="card gasq-card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Connection status</h3>
            <span class="badge {{ ($twilioHealth['ok'] ?? false) ? 'text-bg-success' : 'text-bg-danger' }}">
                {{ ($twilioHealth['ok'] ?? false) ? 'Healthy' : 'Failed' }}
            </span>
        </div>
        <div class="card-body">
            <p class="mb-3">{{ $twilioHealth['summary'] ?? 'No result available.' }}</p>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Key</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(($twilioHealth['details'] ?? []) as $key => $value)
                            <tr>
                                <td class="text-nowrap">{{ $key }}</td>
                                <td>{{ is_array($value) ? json_encode($value) : ($value === null || $value === '' ? '—' : $value) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card gasq-card mb-4">
        <div class="card-header">
            <h3 class="card-title mb-0">Current runtime config</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Key</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($twilioDebug as $key => $value)
                            <tr>
                                <td class="text-nowrap">{{ $key }}</td>
                                <td>{{ $value === null || $value === '' ? '—' : $value }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card gasq-card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Copy diagnostics</h3>
            <button type="button" class="btn btn-sm btn-outline-primary" id="copyTwilioDiagnostics">
                Copy diagnostics
            </button>
        </div>
        <div class="card-body">
            @php
                $diagnosticLines = [
                    'Twilio Health Check',
                    'Summary: ' . (($twilioHealth['summary'] ?? 'No result available.')),
                    'Healthy: ' . (($twilioHealth['ok'] ?? false) ? 'yes' : 'no'),
                ];

                foreach (($twilioHealth['details'] ?? []) as $key => $value) {
                    $diagnosticLines[] = $key . ': ' . (is_array($value) ? json_encode($value) : (($value === null || $value === '') ? '—' : $value));
                }

                $diagnosticLines[] = '';
                $diagnosticLines[] = 'Runtime Config';

                foreach (($twilioDebug ?? []) as $key => $value) {
                    $diagnosticLines[] = $key . ': ' . (($value === null || $value === '') ? '—' : $value);
                }

                $diagnosticText = implode("\n", $diagnosticLines);
            @endphp

            <p class="text-gasq-muted small mb-2">This is a masked snapshot you can safely share with your client or Twilio support.</p>
            <textarea
                id="twilioDiagnosticsText"
                class="form-control font-monospace"
                rows="14"
                readonly
            >{{ $diagnosticText }}</textarea>
            <div class="form-text" id="twilioDiagnosticsStatus">Secrets stay masked in this export.</div>
        </div>
    </div>

    <div class="card gasq-card">
        <div class="card-header">
            <h3 class="card-title mb-0">Send test SMS</h3>
        </div>
        <div class="card-body">
            @if(session('twilio_test_result'))
                <div class="alert {{ session('twilio_test_result.ok') ? 'alert-success' : 'alert-danger' }} mb-3">
                    {{ session('twilio_test_result.message') }}
                    @if(session('twilio_test_result.phone'))
                        <span class="d-block small mt-1">Target: {{ session('twilio_test_result.phone') }}</span>
                    @endif
                </div>
            @endif

            <form action="{{ route('admin.twilio.send-test') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="phone" class="form-label">Destination phone number</label>
                    <input
                        type="text"
                        id="phone"
                        name="phone"
                        value="{{ old('phone') }}"
                        class="form-control @error('phone') is-invalid @enderror"
                        placeholder="+12345678900"
                    >
                    @error('phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Use E.164 format, for example <code>+14706332816</code>.</div>
                </div>

                <div class="mb-3">
                    <label for="body" class="form-label">Message body</label>
                    <textarea
                        id="body"
                        name="body"
                        rows="3"
                        class="form-control @error('body') is-invalid @enderror"
                        placeholder="GASQ Twilio health check message."
                    >{{ old('body', 'GASQ Twilio health check message.') }}</textarea>
                    @error('body')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Send test SMS</button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const button = document.getElementById('copyTwilioDiagnostics');
    const textarea = document.getElementById('twilioDiagnosticsText');
    const status = document.getElementById('twilioDiagnosticsStatus');

    if (!button || !textarea || !status) {
        return;
    }

    button.addEventListener('click', async function () {
        try {
            await navigator.clipboard.writeText(textarea.value);
            status.textContent = 'Diagnostics copied to clipboard.';
        } catch (error) {
            textarea.focus();
            textarea.select();
            status.textContent = 'Copy failed automatically. The diagnostics text has been selected so you can copy it manually.';
        }
    });
});
</script>
@endpush
