@php
    $r = $questionnaire->responses ?? [];
    $vendor = $questionnaire->vendor;
    $job = $questionnaire->jobPosting;
    $buyer = $job?->user;

    $sections = [
        'Part 1 — Vendor Submission Compliance' => [
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
        'Part 1 — Pricing Responsiveness' => [
            'p1_q18_pricing_accepted' => 'Accepts proposed pricing',
            'p1_q19_schedule_accepted' => 'Accepts proposed schedule',
            'p1_q20_pricing_sustainable' => 'Pricing sustainable',
        ],
        'Part 2 — Operational Capacity' => [
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
        'Part 2 — Workforce Sustainment' => [
            'p2_q11_no_excessive_overtime' => 'No excessive OT',
            'p2_q12_turnover_pct' => 'Turnover (%)',
            'p2_q13_retention_months' => 'Retention (months)',
            'p2_q14_employee_benefits' => 'Employee benefits',
            'p2_q15_formal_training' => 'Formal training',
            'p2_q16_background_checks' => 'Background checks',
            'p2_q17_sops_post_orders' => 'SOPs / post orders',
        ],
        'Part 2 — Financial Responsibility' => [
            'p2_q18_payroll_30_45' => 'Payroll sustainable 30–45 days',
            'p2_q19_failed_payroll' => 'Ever failed payroll',
            'p2_q20_lost_contract_staffing' => 'Ever lost contract for staffing',
            'p2_q21_insurances' => 'Insurances maintained',
            'p2_q22_negligent_security_litigation' => 'Negligent security litigation',
            'p2_q23_license_suspended' => 'License suspended/revoked',
        ],
        'Part 2 — Performance & Integrity' => [
            'p2_q24_three_references' => '3 client references',
            'p2_q25_past_performance' => 'Proof of past performance',
        ],
    ];

    $fmt = function ($val) {
        if (is_array($val)) return count($val) ? implode(', ', $val) : '—';
        if ($val === null || $val === '') return '—';
        return $val;
    };

    $reportNumber = 'GASQ ' . now()->format('Y-m-d') . '-VQ' . str_pad((string) $questionnaire->id, 4, '0', STR_PAD_LEFT);
@endphp

@extends('pdf.layouts.gasq-report', [
    'title' => 'GASQ Vendor Qualification Packet',
    'subtitle' => 'Responsive & Responsible Vendor Verification',
    'reportNumber' => $reportNumber,
    'reportType' => 'Buyer-Facing Qualification',
    'contactName' => $vendor?->name,
    'contactCompany' => $vendor?->company ?? $vendor?->vendorProfile?->company_name,
    'contactEmail' => $vendor?->email,
    'contactPhone' => $vendor?->phone,
])

@section('stat_grid')
<table width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <td width="33%" class="stat-grid-label"><p>Responsive Status</p></td>
    <td width="34%" class="stat-grid-label"><p>Responsible Status</p></td>
    <td width="33%" class="stat-grid-label last"><p>Documents Uploaded</p></td>
  </tr>
  <tr>
    <td class="stat-grid-value {{ $questionnaire->is_responsive ? 'bg-green' : 'bg-pink' }}">
      <p class="num" style="font-size:24px;">{{ $questionnaire->is_responsive ? '✓ Responsive' : 'Non-Responsive' }}</p>
      <p class="sub">Submission compliance + pricing</p>
    </td>
    <td class="stat-grid-value {{ $questionnaire->is_responsible ? 'bg-green' : 'bg-pink' }}">
      <p class="num" style="font-size:24px;">{{ $questionnaire->is_responsible ? '✓ Responsible' : 'Non-Responsible' }}</p>
      <p class="sub">Capacity, financial, performance</p>
    </td>
    <td class="stat-grid-value bg-blue last">
      <p class="num">{{ $questionnaire->documents->count() }} / {{ count($documentTypes) }}</p>
      <p class="sub">required documents on file</p>
    </td>
  </tr>
</table>
@endsection

@section('content')

<table width="100%" cellpadding="0" cellspacing="0" class="gasq-mt">
  <tr><td class="gasq-section-band"><p>Engagement Context</p></td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-kv">
  <tr><td>Buyer / Job</td><td class="v">{{ $buyer?->name ?? '—' }} · {{ $job?->title ?? '—' }}</td></tr>
  <tr class="alt"><td>Years in Business</td><td class="v">{{ $fmt($r['p2_q7_years_in_business'] ?? null) }}</td></tr>
  <tr><td>Active Security Personnel</td><td class="v">{{ $fmt($r['p2_q1_active_personnel'] ?? null) }}</td></tr>
  <tr class="alt"><td>Active Client Accounts</td><td class="v">{{ $fmt($r['p2_q3_active_accounts'] ?? null) }}</td></tr>
  <tr><td>24/7 Dispatch</td><td class="v">{{ strtoupper((string) ($r['p2_q8_dispatch_24_7'] ?? '—')) }}</td></tr>
  <tr class="alt">
    <td>Insurances on File</td>
    <td class="v">@php $ins = is_array($r['p2_q21_insurances'] ?? null) ? $r['p2_q21_insurances'] : []; @endphp{{ count($ins) > 0 ? implode(', ', $ins) : '—' }}</td>
  </tr>
  <tr><td>Submitted</td><td class="v">{{ $questionnaire->submitted_at?->format('M j, Y g:i A') ?? '—' }}</td></tr>
</table>

@foreach($sections as $heading => $fields)
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-mt">
  <tr><td class="gasq-section-band"><p>{{ $heading }}</p></td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-kv">
  @foreach($fields as $key => $label)
    <tr class="{{ $loop->iteration % 2 === 0 ? 'alt' : '' }}">
      <td>{{ $label }}</td>
      <td class="v">{{ $fmt($r[$key] ?? null) }}</td>
    </tr>
  @endforeach
</table>
@endforeach

<table width="100%" cellpadding="0" cellspacing="0" class="gasq-mt">
  <tr><td class="gasq-section-band"><p>Uploaded Documents</p></td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-kv">
  @forelse($questionnaire->documents as $doc)
    <tr class="{{ $loop->iteration % 2 === 0 ? 'alt' : '' }}">
      <td>{{ $documentTypes[$doc->document_type] ?? $doc->document_type }}</td>
      <td class="v" style="font-weight:normal; color:#374151;">{{ $doc->fileUpload?->filename ?? '—' }}</td>
    </tr>
  @empty
    <tr><td colspan="2" style="color:#6b7280;">No documents uploaded.</td></tr>
  @endforelse
</table>

<p class="gasq-note">
    This packet is generated by the GASQ Workforce-to-Post™ Qualification System. All figures, qualifications, and verifications reflect the data captured at the time of submission. Backed by the GASQ Price Lock Guarantee and Vendor Replacement Guarantee.
</p>

@endsection
