@php
    /** @var \App\Models\VendorQuestionnaire $questionnaire */
    $r = $questionnaire->responses ?? [];
    $showDocumentLinks = $showDocumentLinks ?? false;

    $sections = [
        'Part 1 — Section A: Vendor Submission Compliance' => [
            'p1_q1_legal_name' => 'Company Legal Name',
            'p1_q1_address' => 'Business Physical Address',
            'p1_q2_dba' => 'DBA Name',
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
            'p1_q15_response_time' => 'Response time requirements',
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
            'p2_q3_active_accounts' => 'Active client accounts',
            'p2_q4_weekly_billable_hours' => 'Weekly billable hours',
            'p2_q5_similar_size' => 'Services similar size accounts',
            'p2_q6_largest_account' => 'Largest account',
            'p2_q7_years_in_business' => 'Years in business',
            'p2_q8_dispatch_24_7' => '24/7 dispatch',
            'p2_q9_after_hours_supervisors' => 'After-hours supervisors',
            'p2_q10_emergency_replacement' => 'Emergency replacement personnel',
        ],
        'Part 2 — Section B: Workforce Sustainment' => [
            'p2_q11_no_excessive_overtime' => 'Sustains without excessive OT',
            'p2_q12_turnover_pct' => 'Turnover rate (%)',
            'p2_q13_retention_months' => 'Retention period (months)',
            'p2_q14_employee_benefits' => 'Employee benefits',
            'p2_q15_formal_training' => 'Formal training programs',
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
        if (is_array($val)) {
            return count($val) ? implode(', ', $val) : '—';
        }
        if ($val === null || $val === '') {
            return '—';
        }
        return $val;
    };
@endphp

@foreach($sections as $heading => $fields)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-light"><strong>{{ $heading }}</strong></div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <tbody>
                    @foreach($fields as $key => $label)
                        <tr>
                            <td class="text-muted" style="width:45%">{{ $label }}</td>
                            <td><strong>{{ $fmt($r[$key] ?? null) }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endforeach

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-light"><strong>Uploaded Documents</strong></div>
    <ul class="list-group list-group-flush">
        @forelse($questionnaire->documents as $doc)
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>
                    <strong>{{ $documentTypes[$doc->document_type] ?? $doc->document_type }}</strong>
                    <span class="text-muted small ms-2">{{ $doc->fileUpload?->filename }}</span>
                </span>
                @if($showDocumentLinks && $doc->fileUpload)
                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($doc->fileUpload->file_path) }}"
                       target="_blank" class="btn btn-sm btn-outline-primary">View</a>
                @endif
            </li>
        @empty
            <li class="list-group-item text-muted">No documents uploaded.</li>
        @endforelse
    </ul>
</div>
