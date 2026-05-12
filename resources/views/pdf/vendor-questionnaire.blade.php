@php
    $r = $questionnaire->responses ?? [];
    $vendor = $questionnaire->vendor;
    $job = $questionnaire->jobPosting;
    $buyer = $job?->user;

    $sections = [
        'Part 1 — Section A: Vendor Submission Compliance' => [
            'p1_q1_legal_name' => 'Company Legal Name',
            'p1_q1_address' => 'Business Physical Address',
            'p1_q2_dba' => 'DBA',
            'p1_q3_contact_name' => 'Contact Name',
            'p1_q3_contact_title' => 'Contact Title',
            'p1_q3_contact_email' => 'Contact Email',
            'p1_q3_contact_phone' => 'Contact Phone',
            'p1_q4_licensed' => 'Licensed in this state',
            'p1_q6_start_date' => 'Can meet start date',
            'p1_q7_coverage_hours' => 'Can meet coverage hours',
            'p1_q8_staff_personnel' => 'Can provide required staff',
            'p1_q9_uniform' => 'Uniform compliance',
            'p1_q10_reporting' => 'Reporting compliance',
            'p1_q11_technology' => 'Technology capabilities',
            'p1_q11_technology_compliance' => 'Technology requirements compliance',
            'p1_q12_insurance_minimums' => 'Insurance minimums',
            'p1_q13_wage' => 'Wage requirements',
            'p1_q14_training' => 'Training/certification',
            'p1_q15_response_time' => 'Response time',
            'p1_q16_scope_reviewed' => 'Scope reviewed',
            'p1_q17_terms_agreed' => 'Terms agreed',
        ],
        'Part 1 — Section B: Pricing Responsiveness' => [
            'p1_q18_pricing_accepted' => 'Accepts proposed pricing',
            'p1_q19_schedule_accepted' => 'Accepts proposed schedule',
            'p1_q20_pricing_sustainable' => 'Pricing sustainable',
        ],
        'Part 2 — Section A: Operational Capacity' => [
            'p2_q1_active_personnel' => 'Active security personnel',
            'p2_q2_supervisors' => 'Supervisors',
            'p2_q3_active_accounts' => 'Active accounts',
            'p2_q4_weekly_billable_hours' => 'Weekly billable hours',
            'p2_q5_similar_size' => 'Services similar size accounts',
            'p2_q6_largest_account' => 'Largest account',
            'p2_q7_years_in_business' => 'Years in business',
            'p2_q8_dispatch_24_7' => '24/7 dispatch',
            'p2_q9_after_hours_supervisors' => 'After-hours supervisors',
            'p2_q10_emergency_replacement' => 'Emergency replacement personnel',
        ],
        'Part 2 — Section B: Workforce Sustainment' => [
            'p2_q11_no_excessive_overtime' => 'No excessive OT',
            'p2_q12_turnover_pct' => 'Turnover (%)',
            'p2_q13_retention_months' => 'Retention (months)',
            'p2_q14_employee_benefits' => 'Employee benefits',
            'p2_q15_formal_training' => 'Formal training',
            'p2_q16_background_checks' => 'Background checks',
            'p2_q17_sops_post_orders' => 'SOPs / post orders',
        ],
        'Part 2 — Section C: Financial Responsibility' => [
            'p2_q18_payroll_30_45' => 'Payroll sustainable 30–45 days',
            'p2_q19_failed_payroll' => 'Ever failed payroll',
            'p2_q20_lost_contract_staffing' => 'Ever lost contract for staffing',
            'p2_q21_insurances' => 'Insurances maintained',
            'p2_q22_negligent_security_litigation' => 'Negligent security litigation',
            'p2_q23_license_suspended' => 'License suspended/revoked',
        ],
        'Part 2 — Section D: Performance & Integrity' => [
            'p2_q24_three_references' => '3 client references',
            'p2_q25_past_performance' => 'Proof of past performance',
        ],
    ];

    $fmt = function ($val) {
        if (is_array($val)) return count($val) ? implode(', ', $val) : '—';
        if ($val === null || $val === '') return '—';
        return $val;
    };
@endphp

@extends('pdf.layouts.gasq-base', [
    'title' => 'Vendor Qualification Packet',
    'subtitle' => 'GASQ Responsive & Responsible Vendor Verification',
    'preparedFor' => $buyer?->name . ($buyer?->company ? ' — ' . $buyer->company : ''),
    'preparedBy' => $vendor?->name . ($vendor?->company ? ' — ' . $vendor->company : ''),
    'reportDate' => $questionnaire->submitted_at?->format('F j, Y'),
    'referenceNumber' => 'VQ-' . str_pad((string) $questionnaire->id, 6, '0', STR_PAD_LEFT),
])

@section('cover_summary')
    <div class="badge-row">
        <span class="badge {{ $questionnaire->is_responsive ? 'ok' : 'warn' }}">
            {{ $questionnaire->is_responsive ? '✓ RESPONSIVE' : 'NON-RESPONSIVE' }}
        </span>
        <span class="badge {{ $questionnaire->is_responsible ? 'ok' : 'warn' }}">
            {{ $questionnaire->is_responsible ? '✓ RESPONSIBLE' : 'NON-RESPONSIBLE' }}
        </span>
    </div>

    <p style="margin-top:18px;">
        <strong>{{ $vendor?->name ?? 'The vendor' }}</strong>@if($vendor?->company) ({{ $vendor->company }})@endif has completed the GASQ Vendor Qualification Questionnaire for
        <strong>"{{ $job?->title ?? 'this engagement' }}"</strong>{{ $job?->location ? ' in ' . $job->location : '' }}.
    </p>

    <p>
        This packet contains the vendor's full set of compliance, operational capacity, workforce sustainment, financial responsibility, and performance responses, along with their uploaded supporting documents.
    </p>

    <h3 style="margin-top:24px;">At a glance</h3>
    <table class="kv-table" style="margin-top:6px;">
        <tr>
            <td class="label">Years in business</td>
            <td><strong>{{ $fmt($r['p2_q7_years_in_business'] ?? null) }}</strong></td>
        </tr>
        <tr>
            <td class="label">Active security personnel</td>
            <td><strong>{{ $fmt($r['p2_q1_active_personnel'] ?? null) }}</strong></td>
        </tr>
        <tr>
            <td class="label">Active client accounts</td>
            <td><strong>{{ $fmt($r['p2_q3_active_accounts'] ?? null) }}</strong></td>
        </tr>
        <tr>
            <td class="label">24/7 dispatch</td>
            <td><strong>{{ strtoupper((string) ($r['p2_q8_dispatch_24_7'] ?? '—')) }}</strong></td>
        </tr>
        <tr>
            <td class="label">Insurances on file</td>
            <td>
                @php $ins = is_array($r['p2_q21_insurances'] ?? null) ? $r['p2_q21_insurances'] : []; @endphp
                {{ count($ins) > 0 ? implode(', ', $ins) : '—' }}
            </td>
        </tr>
        <tr>
            <td class="label">Documents uploaded</td>
            <td><strong>{{ $questionnaire->documents->count() }} of {{ count($documentTypes) }}</strong></td>
        </tr>
    </table>

    <div class="gasq-protection-block">
        <h3>GASQ Protections</h3>
        <p class="small" style="margin:0;">
            This engagement is backed by the GASQ <strong>Price Lock Guarantee</strong> — your approved pricing is locked through the engagement — and the <strong>Vendor Replacement Guarantee</strong> — if the selected vendor fails to perform, GASQ steps in to replace them at the same or higher service level.
        </p>
    </div>
@endsection

@section('content')

@foreach($sections as $heading => $fields)
    <h2>{{ $heading }}</h2>
    <table>
        @foreach($fields as $key => $label)
            <tr>
                <td class="label">{{ $label }}</td>
                <td><strong>{{ $fmt($r[$key] ?? null) }}</strong></td>
            </tr>
        @endforeach
    </table>
@endforeach

<h2>Uploaded Documents</h2>
<table>
    <thead>
        <tr><th>Document type</th><th>File</th></tr>
    </thead>
    <tbody>
        @forelse($questionnaire->documents as $doc)
            <tr>
                <td class="label">{{ $documentTypes[$doc->document_type] ?? $doc->document_type }}</td>
                <td>{{ $doc->fileUpload?->filename ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="2" class="muted">No documents uploaded.</td></tr>
        @endforelse
    </tbody>
</table>

@endsection
