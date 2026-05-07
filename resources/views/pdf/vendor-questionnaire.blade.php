<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Vendor Qualification Response</title>
<style>
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #222; }
    h1 { font-size: 18px; margin: 0 0 4px; }
    h2 { font-size: 13px; margin: 18px 0 6px; padding-bottom: 3px; border-bottom: 1px solid #999; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    td { padding: 4px 6px; vertical-align: top; border-bottom: 1px solid #eee; }
    td.label { color: #666; width: 45%; }
    .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 10px; }
    .ok { background: #d1e7dd; color: #0a3622; }
    .bad { background: #fff3cd; color: #664d03; }
    .meta { color: #666; font-size: 10px; }
</style>
</head>
<body>

@php
    $r = $questionnaire->responses ?? [];
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

<h1>GASQ Vendor Qualification Response</h1>
<p class="meta">
    Vendor: <strong>{{ $questionnaire->vendor?->name }}</strong><br>
    Job: <strong>{{ $questionnaire->jobPosting?->title }}</strong><br>
    Submitted: {{ $questionnaire->submitted_at?->format('M j, Y g:i A') }}
</p>

<p>
    <span class="badge {{ $questionnaire->is_responsive ? 'ok' : 'bad' }}">
        {{ $questionnaire->is_responsive ? 'RESPONSIVE' : 'Non-responsive' }}
    </span>
    <span class="badge {{ $questionnaire->is_responsible ? 'ok' : 'bad' }}">
        {{ $questionnaire->is_responsible ? 'RESPONSIBLE' : 'Non-responsible' }}
    </span>
</p>

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
    @forelse($questionnaire->documents as $doc)
        <tr>
            <td class="label">{{ $documentTypes[$doc->document_type] ?? $doc->document_type }}</td>
            <td>{{ $doc->fileUpload?->filename }}</td>
        </tr>
    @empty
        <tr><td colspan="2" class="meta">No documents uploaded.</td></tr>
    @endforelse
</table>

</body>
</html>
