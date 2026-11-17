{{-- Vendor preview of the buyer-facing comparison report, plus the PDF send. --}}
@extends('layouts.app')
@section('title', 'Cost Comparison · '.$estimate->public_id)
@section('header_variant', 'dashboard')

@section('content')
<div class="container py-4" style="max-width:960px;">

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <div class="text-uppercase small text-muted" style="letter-spacing:.12em;">Private estimate {{ $estimate->public_id }}</div>
            <h1 class="h4 mb-1" style="color:#0b2447;">Buyer comparison report</h1>
            <p class="text-muted small mb-0">
                This is what {{ $estimate->buyer_name ?: $estimate->buyer_email }} sees. Status: {{ $estimate->statusLabel() }}.
            </p>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            @include('private-estimates._comparison', ['r' => $r])
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-2" style="color:#0b2447;">Download the PDF</h2>
            <p class="text-muted small">
                Two pages: the comparison above, then the method, certification and disclaimer. Set a password
                and the buyer must enter it to open the file. Leave it blank to send an unlocked copy.
            </p>
            <form method="POST" action="{{ route('private-estimates.report.download', $estimate) }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-sm-6">
                    <label class="form-label small text-muted" for="pdf_password">PDF password (optional)</label>
                    <input type="text" class="form-control" id="pdf_password" name="pdf_password"
                           minlength="8" maxlength="32" autocomplete="off" placeholder="8 to 32 characters">
                </div>
                <div class="col-sm-auto">
                    <button type="submit" class="btn btn-primary">Download PDF</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
