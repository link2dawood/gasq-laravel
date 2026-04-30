@extends('layouts.app')
@section('title', 'Vendor Leads')
@section('header_variant', 'dashboard')
@section('main_class', 'py-0')

@push('styles')
<style>
    .vendor-leads-shell {
        min-height: calc(100vh - 72px);
        background:
            radial-gradient(circle at top left, rgba(255, 130, 95, 0.10), transparent 24rem),
            linear-gradient(180deg, #f7f8fc 0%, #f3f5fa 100%);
    }
    .vendor-leads-container {
        width: min(100%, 1480px);
        margin: 0 auto;
        padding: 1.15rem 1rem 1.35rem;
    }
    .vendor-leads-frame {
        border: 1px solid #dbe1ec;
        border-radius: 1.25rem;
        overflow: hidden;
        background: rgba(255, 255, 255, 0.92);
        box-shadow: 0 18px 50px rgba(20, 30, 55, 0.06);
        backdrop-filter: blur(6px);
    }
    .vendor-leads-layout {
        display: grid;
        grid-template-columns: 25rem minmax(0, 1fr);
        min-height: calc(100vh - 120px);
    }
    .vendor-leads-list {
        border-right: 1px solid #dbe1ec;
        background: rgba(248, 250, 253, 0.88);
        overflow-y: auto;
        max-height: calc(100vh - 120px);
    }
    .vendor-leads-detail {
        overflow-y: auto;
        max-height: calc(100vh - 120px);
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.94) 0%, rgba(248, 250, 253, 0.98) 100%);
    }
    .lead-row {
        display: block;
        padding: 1rem 1.1rem;
        color: inherit;
        text-decoration: none;
        border-bottom: 1px solid #e5e9f2;
        background: transparent;
        transition: background-color .18s ease, border-color .18s ease, box-shadow .18s ease;
    }
    .lead-row:hover,
    .lead-row.is-active {
        background: rgba(255, 255, 255, 0.98);
        color: inherit;
    }
    .lead-row.is-active {
        box-shadow: inset 4px 0 0 #ff825f;
    }
    .lead-age {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 5.5rem;
        padding: .4rem .72rem;
        border-radius: 999px;
        background: #1fb7e7;
        color: #fff;
        font-size: .82rem;
        font-weight: 700;
        letter-spacing: .02em;
    }
    .lead-card-topline {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .9rem;
        margin-bottom: .65rem;
    }
    .lead-card-subtitle {
        font-size: 1rem;
        font-weight: 700;
        line-height: 1.2;
        color: #20283b;
    }
    .lead-card-title {
        margin: .2rem 0 0;
        font-size: 2rem;
        line-height: 1.04;
        font-weight: 700;
        color: #1f2433;
    }
    .lead-card-meta {
        font-size: 1rem;
        line-height: 1.4;
        color: #2f384c;
    }
    .lead-card-summary {
        margin-bottom: .45rem;
        font-size: .98rem;
        line-height: 1.45;
        color: #2b3346;
    }
    .lead-card-extra {
        margin-bottom: .7rem;
        font-size: .96rem;
        line-height: 1.45;
        color: #0b8f51;
        font-weight: 700;
    }
    .lead-card-extra span {
        color: #2b3346;
        font-weight: 500;
    }
    .lead-credits {
        font-weight: 800;
        font-size: .96rem;
        color: #242d40;
    }
    .lead-detail-inner {
        max-width: 60rem;
        padding: 1.25rem 1.35rem 1.6rem;
    }
    .lead-detail-header {
        margin-bottom: 1rem;
    }
    .lead-detail-subtitle {
        font-size: 1.1rem;
        font-weight: 700;
        line-height: 1.2;
        color: #232b3d;
    }
    .lead-detail-title {
        margin: .2rem 0 .85rem;
        font-size: clamp(2.15rem, 3vw, 3.3rem);
        line-height: 1.02;
        font-weight: 700;
        color: #1c2232;
    }
    .lead-contact-line {
        font-size: 1.05rem;
        line-height: 1.4;
        color: #2d3446;
    }
    .lead-verify {
        display: grid;
        gap: .35rem;
    }
    .lead-verify li {
        margin-bottom: 0;
        font-size: 1rem;
        line-height: 1.45;
        color: #273043;
    }
    .lead-progress-box {
        display: inline-flex;
        align-items: center;
        gap: .65rem;
        flex-wrap: wrap;
        padding: .85rem 1rem;
        border: 1px solid #d8dde8;
        border-radius: 1rem;
        background: rgba(255, 255, 255, 0.95);
    }
    .lead-progress-dot {
        width: .95rem;
        height: .95rem;
        border-radius: .22rem;
        background: #d7dde8;
        display: inline-block;
    }
    .lead-progress-dot.is-on { background: #17bf1c; }
    .lead-primary-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 9.5rem;
        padding: .82rem 1.3rem;
        border-radius: 999px;
        background: #ff825f;
        color: #fff;
        text-decoration: none;
        font-weight: 700;
        font-size: .98rem;
    }
    .lead-primary-btn:hover { color: #fff; background: #f36e47; }
    .lead-location {
        font-size: 1rem;
        line-height: 1.45;
        color: #2d3446;
    }
    .lead-summary {
        font-size: 1.05rem;
        line-height: 1.45;
        color: #232b3d;
    }
    .lead-detail-block {
        padding-top: 1rem;
        border-top: 1px solid #dde2eb;
        margin-top: 1.35rem;
    }
    .lead-detail-heading {
        margin-bottom: .9rem;
        font-size: 1.6rem;
        line-height: 1.1;
        font-weight: 700;
        color: #1f2738;
    }
    .lead-detail-row {
        margin-bottom: 1rem;
    }
    .lead-detail-label {
        margin-bottom: .2rem;
        font-size: .92rem;
        line-height: 1.35;
        color: #7a8498;
        font-weight: 700;
    }
    .lead-detail-value {
        font-size: 1rem;
        line-height: 1.5;
        color: #2a3347;
    }
    .lead-empty {
        display: grid;
        place-items: center;
        min-height: calc(100vh - 120px);
        padding: 2rem;
        text-align: center;
    }
    @media (max-width: 1199.98px) {
        .vendor-leads-container { padding-inline: .9rem; }
        .vendor-leads-layout { grid-template-columns: 22rem 1fr; }
        .lead-card-title { font-size: 1.72rem; }
    }
    @media (max-width: 991.98px) {
        .vendor-leads-layout { grid-template-columns: 1fr; }
        .vendor-leads-list, .vendor-leads-detail { max-height: none; }
        .vendor-leads-list { border-right: 0; border-bottom: 1px solid #d8dde8; }
        .vendor-leads-container { padding: .75rem .75rem 1rem; }
        .lead-detail-inner { padding: 1rem 1rem 1.35rem; }
        .lead-card-title { font-size: 1.6rem; }
        .lead-detail-title { font-size: 2.1rem; }
    }
</style>
@endpush

@section('content')
<div class="vendor-leads-shell">
    <div class="vendor-leads-container">
        <div class="vendor-leads-frame">
            @if($leadItems->isEmpty())
                <div class="lead-empty">
                    <div>
                        <h1 class="display-6 fw-semibold mb-3">No leads yet</h1>
                        <p class="fs-5 text-gasq-muted mb-4">Your invited opportunities will appear here once leads start coming in.</p>
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
                                <div class="lead-card-topline">
                                    <div>
                                        <div class="lead-card-subtitle">{{ $item['subtitle'] }}</div>
                                        <div class="lead-card-title">{{ $item['title'] }}</div>
                                    </div>
                                    <span class="lead-age">{{ $item['age_badge'] }}</span>
                                </div>
                                <div class="lead-card-meta mb-2"><i class="fa fa-location-dot me-2 text-gasq-muted"></i>{{ $item['location'] }}</div>
                                <div class="lead-card-summary">{{ $item['summary'] }}</div>
                                <div class="lead-card-extra">Additional details: <span>{{ $item['additional_details'] }}</span></div>
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
                            <div class="lead-detail-header">
                                <div class="lead-detail-subtitle">{{ $selected['subtitle'] }}</div>
                                <h1 class="lead-detail-title">{{ $selected['title'] }}</h1>
                                <div class="lead-contact-line mb-1"><i class="fa fa-phone me-2"></i>{{ $selected['buyer_phone'] }}</div>
                                <div class="lead-contact-line mb-0"><i class="fa fa-envelope me-2"></i>{{ $selected['buyer_email'] }}</div>
                            </div>

                            <ul class="lead-verify list-unstyled mb-3">
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
                                <span class="fs-5">{{ $selected['response_label'] }}</span>
                            </div>

                            <div class="mb-3">
                                <a href="{{ $selected['action_url'] }}" class="lead-primary-btn">{{ $selected['buyer_contact_action'] }}</a>
                            </div>

                            <div class="lead-location mb-1"><i class="fa fa-location-dot me-2"></i>{{ $selected['location'] }}</div>
                            <div class="lead-summary mb-3">{{ $selected['summary'] }}</div>

                            <div class="lead-detail-block">
                                <h2 class="lead-detail-heading">Details</h2>
                                @foreach($selected['detail_rows'] as $row)
                                    <div class="lead-detail-row">
                                        <div class="lead-detail-label">{{ $row['label'] }}</div>
                                        <div class="lead-detail-value">{{ $row['value'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </section>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
