@extends('layouts.app')
@section('title', 'Vendor Leads')
@section('header_variant', 'dashboard')
@section('main_class', 'py-0')

@push('styles')
<style>
    .vendor-leads-shell { min-height: calc(100vh - 72px); background: #f5f7fb; }
    .vendor-leads-layout { display: grid; grid-template-columns: 33rem 1fr; min-height: calc(100vh - 72px); }
    .vendor-leads-list { border-right: 1px solid #d8dde8; background: #fff; overflow-y: auto; max-height: calc(100vh - 72px); }
    .vendor-leads-detail { overflow-y: auto; max-height: calc(100vh - 72px); }
    .lead-row {
        display: block; padding: 1.15rem 1.25rem; color: inherit; text-decoration: none;
        border-bottom: 1px solid #e5e9f2; background: #fff;
    }
    .lead-row:hover, .lead-row.is-active { background: #f7faff; color: inherit; }
    .lead-age {
        display: inline-flex; align-items: center; justify-content: center; min-width: 6rem;
        padding: .5rem .8rem; border-radius: .75rem; background: #18c2f0; color: #fff;
        font-size: .95rem; font-weight: 700;
    }
    .lead-credits { font-weight: 800; font-size: 1rem; }
    .lead-detail-inner { padding: 1.4rem 1.6rem 2rem; }
    .lead-verify li { margin-bottom: .4rem; }
    .lead-progress-box {
        display: inline-flex; align-items: center; gap: .7rem; flex-wrap: wrap;
        padding: 1rem 1.2rem; border: 1px solid #d8dde8; border-radius: .9rem; background: #fff;
    }
    .lead-progress-dot {
        width: 1rem; height: 1rem; border-radius: .2rem; background: #d7dde8; display: inline-block;
    }
    .lead-progress-dot.is-on { background: #17bf1c; }
    .lead-primary-btn {
        display: inline-flex; align-items: center; justify-content: center; min-width: 10rem;
        padding: .9rem 1.45rem; border-radius: 999px; background: #ff825f; color: #fff;
        text-decoration: none; font-weight: 700;
    }
    .lead-primary-btn:hover { color: #fff; background: #f36e47; }
    .lead-detail-block { padding-top: 1.2rem; border-top: 1px solid #dde2eb; margin-top: 2rem; }
    .lead-empty { display: grid; place-items: center; min-height: calc(100vh - 72px); padding: 2rem; text-align: center; }
    @media (max-width: 1199.98px) {
        .vendor-leads-layout { grid-template-columns: 26rem 1fr; }
    }
    @media (max-width: 991.98px) {
        .vendor-leads-layout { grid-template-columns: 1fr; }
        .vendor-leads-list, .vendor-leads-detail { max-height: none; }
        .vendor-leads-list { border-right: 0; border-bottom: 1px solid #d8dde8; }
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">
<div class="vendor-leads-shell">
    @if($leadItems->isEmpty())
        <div class="lead-empty">
            <div>
                <h1 class="display-6 fw-semibold mb-3">No leads yet</h1>
                <p class="fs-4 text-gasq-muted mb-4">Your invited opportunities will appear here once leads start coming in.</p>
                <a href="{{ route('home') }}" class="lead-primary-btn">Back to Dashboard</a>
            </div>
        </div>
    @else
        <div class="vendor-leads-layout">
            <aside class="vendor-leads-list">
                @foreach($leadItems as $item)
                    <a
                        href="{{ route('vendor-leads.index', array_filter(['lead' => $item['key'], 'view' => $leadView ?: null])) }}"
                        class="lead-row {{ ($selectedLead['key'] ?? null) === $item['key'] ? 'is-active' : '' }}"
                    >
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div>
                                <div class="fs-3 fw-semibold">{{ $item['subtitle'] }}</div>
                                <div class="h1 fw-semibold lh-sm mt-1 mb-0">{{ $item['title'] }}</div>
                            </div>
                            <span class="lead-age">{{ $item['age_badge'] }}</span>
                        </div>
                        <div class="fs-5 mb-1"><i class="fa fa-location-dot me-2 text-gasq-muted"></i>{{ $item['location'] }}</div>
                        <div class="fs-4 mb-1">{{ $item['summary'] }}</div>
                        <div class="fs-5 text-success fw-semibold mb-2">Additional details: <span class="fw-normal text-dark">{{ $item['additional_details'] }}</span></div>
                        <div class="lead-credits"><i class="fa fa-coins me-2 text-gasq-muted"></i>{{ number_format((int) $item['credits']) }} credits</div>
                    </a>
                @endforeach
            </aside>

            <section class="vendor-leads-detail">
                @php
                    $selected = $selectedLead;
                    $filledDots = min((int) $selected['response_count'], (int) $selected['response_denominator']);
                @endphp
                <div class="lead-detail-inner">
                    <div class="mb-3">
                        <div class="display-6 fw-semibold">{{ $selected['subtitle'] }}</div>
                        <h1 class="display-6 fw-semibold mt-1 mb-3">{{ $selected['title'] }}</h1>
                        <div class="fs-3 mb-1"><i class="fa fa-phone me-2"></i>{{ $selected['buyer_phone'] }}</div>
                        <div class="fs-3 mb-3"><i class="fa fa-envelope me-2"></i>{{ $selected['buyer_email'] }}</div>
                    </div>

                    <ul class="lead-verify list-unstyled fs-4 mb-3">
                        @foreach($selected['verification_rows'] as $label => $value)
                            <li>
                                <i class="fa fa-square-check me-2 text-success"></i>
                                {{ $label }}:
                                @if(is_bool($value))
                                    {{ $value ? 'Yes' : 'No' }}
                                @else
                                    {{ $value }}
                                @endif
                            </li>
                        @endforeach
                    </ul>

                    <div class="lead-progress-box mb-3">
                        @for($i = 1; $i <= (int) $selected['response_denominator']; $i++)
                            <span class="lead-progress-dot {{ $i <= $filledDots ? 'is-on' : '' }}"></span>
                        @endfor
                        <span class="fs-4">{{ $selected['response_label'] }}</span>
                    </div>

                    <div class="mb-3">
                        <a href="{{ $selected['action_url'] }}" class="lead-primary-btn">{{ $selected['buyer_contact_action'] }}</a>
                    </div>

                    <div class="fs-4 mb-1"><i class="fa fa-location-dot me-2"></i>{{ $selected['location'] }}</div>
                    <div class="fs-3 mb-3">{{ $selected['summary'] }}</div>

                    <div class="lead-detail-block">
                        <h2 class="h1 fw-semibold mb-3">Details</h2>
                        @foreach($selected['detail_rows'] as $row)
                            <div class="mb-3">
                                <div class="fs-4 text-gasq-muted fw-semibold">{{ $row['label'] }}</div>
                                <div class="fs-3">{{ $row['value'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>
    @endif
</div>
</div>
@endsection
