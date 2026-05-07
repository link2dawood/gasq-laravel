@extends('layouts.app')

@section('title', 'Vendor Qualification Questionnaire')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-lg-10 mx-auto">

            <div class="mb-4">
                <h2 class="mb-1">GASQ Vendor Qualification Questionnaire</h2>
                <p class="text-muted mb-0">
                    <small>
                        “GASQ does not simply identify vendors willing to accept a price. We identify vendors capable of sustaining the workforce necessary to successfully perform the contract at that price.”
                    </small>
                </p>
            </div>

            @php
                $stepLabels = [
                    1 => 'Submission Compliance',
                    2 => 'Pricing Responsiveness',
                    3 => 'Operational Capacity',
                    4 => 'Workforce Sustainment',
                    5 => 'Financial Responsibility',
                    6 => 'Performance & Review',
                ];
                $progressPct = (int) round(($step / $totalSteps) * 100);
            @endphp

            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between mb-2">
                        <strong>Step {{ $step }} of {{ $totalSteps }}: {{ $stepLabels[$step] }}</strong>
                        <span class="text-muted small">{{ $progressPct }}% complete</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $progressPct }}%"></div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        @foreach($stepLabels as $i => $label)
                            <span class="badge {{ $i < $step ? 'bg-success' : ($i === $step ? 'bg-primary' : 'bg-light text-muted') }}">
                                {{ $i }}. {{ $label }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('blocking_failures'))
                <div class="alert alert-danger">
                    <strong>Submission blocked.</strong> Resolve the following before resubmitting:
                    <ul class="mb-0 mt-2">
                        @foreach(session('blocking_failures') as $f)
                            <li>{{ $f['reason'] ?? '' }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST"
                  action="{{ route('vendor-questionnaires.save-step', ['questionnaire' => $questionnaire->id, 'step' => $step]) }}"
                  enctype="multipart/form-data">
                @csrf

                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        @include('vendor-questionnaires.steps.step' . $step, [
                            'questionnaire' => $questionnaire,
                            'responses' => $responses,
                            'documents' => $documents ?? collect(),
                            'documentTypes' => $documentTypes ?? [],
                        ])
                    </div>

                    <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            @if($step > 1)
                                <button type="submit" name="action" value="prev" class="btn btn-outline-secondary">
                                    &larr; Back
                                </button>
                            @endif
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" name="action" value="save_exit" class="btn btn-outline-primary">
                                Save &amp; Exit
                            </button>
                            @if($step < $totalSteps)
                                <button type="submit" name="action" value="next" class="btn btn-primary">
                                    Save &amp; Continue &rarr;
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </form>

            @if($step === $totalSteps)
                <form method="POST"
                      action="{{ route('vendor-questionnaires.submit', ['questionnaire' => $questionnaire->id]) }}"
                      class="mt-3">
                    @csrf
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-success btn-lg">
                            Send Response to Buyer
                        </button>
                    </div>
                </form>
            @endif

        </div>
    </div>
</div>
@endsection
