<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJobPostingRequest;
use App\Models\JobPosting;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class JobPostingController extends Controller
{
    /**
     * Fields captured from the buyer questionnaire and persisted as a per-posting snapshot.
     *
     * @var list<string>
     */
    private const QUESTIONNAIRE_FIELDS = [
        'contact_name',
        'contact_job_title',
        'organization_name',
        'property_site_name',
        'contact_email',
        'contact_phone',
        'business_address',
        'preferred_contact_method',
        'best_time_to_contact',
        'final_decision_maker',
        'approval_authority',
        'final_approver_name',
        'knows_true_inhouse_cost',
        'project_readiness_reasons',
        'service_start_timeline',
        'funds_approval_status',
        'budget_type',
        'budget_amount_range',
        'true_internal_cost_calculated',
        'if_pricing_exceeds',
        'current_security_setup',
        'is_replacing_provider',
        'multiple_bids_required',
        'willing_adjust_scope_to_budget',
        'move_forward_if_accepted',
        'risk_assessment_last_12_months',
        'multiple_locations',
        'locations_count',
        'property_type_other',
        'service_types',
        'service_type_other',
        'request_type',
        'desired_contract_term',
        'primary_reason',
        'hours_per_day',
        'days_per_week',
        'weeks_per_year',
        'shifts_needed',
        'assignment_type',
        'patrol_types',
        'duties_required',
        'duties_other',
        'service_package_expectation',
        'hands_off_expected',
        'has_written_post_orders',
        'supporting_documents',
        'known_site_risks',
        'budget_format',
        'hourly_budget',
        'monthly_budget',
        'annual_budget',
        'willing_post_offer',
        'allow_scope_adjustment',
        'cost_comparison_requested',
        'officer_licensing_required',
        'background_checks_required',
        'drug_testing_required',
        'uniformed_officers_required',
        'insurance_minimums_required',
        'compliance_terms',
        'vendor_response_deadline',
        'additional_notes_to_vendors',
        'buyer_certification',
        'consent_to_contact',
    ];

    public function index(Request $request): View
    {
        $query = JobPosting::with(['user:id,name', 'bids' => fn ($q) => $q->with('user:id,name,company')]);

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $query->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });

        $jobs = $query->latest()->paginate(15)->withQueryString();

        return view('jobs.index', compact('jobs'));
    }

    public function create(): View|RedirectResponse
    {
        if (! auth()->user()->isBuyer()) {
            return redirect()->route('job-board')->with('error', 'Only buyers can post jobs.');
        }
        return view('jobs.create');
    }

    public function store(StoreJobPostingRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $data['supporting_documents'] = $this->storeSupportingDocuments($request);

        $questionnaireData = $this->buildQuestionnaireData($data, $request);
        $data['questionnaire_data'] = $questionnaireData;
        $budgetFormat = (string) ($data['budget_format'] ?? '');
        if ($budgetFormat === 'hourly_budget') {
            $data['budget_min'] = $data['hourly_budget'] ?? null;
            $data['budget_max'] = $data['hourly_budget'] ?? null;
        } elseif ($budgetFormat === 'monthly_budget') {
            $data['budget_min'] = $data['monthly_budget'] ?? null;
            $data['budget_max'] = $data['monthly_budget'] ?? null;
        } elseif ($budgetFormat === 'annual_budget') {
            $data['budget_min'] = $data['annual_budget'] ?? null;
            $data['budget_max'] = $data['annual_budget'] ?? null;
        }

        $data['description'] = implode("\n", array_filter([
            'Primary reason: ' . ($data['primary_reason'] ?? 'N/A'),
            'Property / site name: ' . ($data['property_site_name'] ?? 'N/A'),
            'Property type detail: ' . (($data['property_type_other'] ?? '') !== '' ? $data['property_type_other'] : 'Not provided'),
            'Request type: ' . str_replace('_', ' ', (string) ($data['request_type'] ?? '')),
            'Contract term: ' . ($data['desired_contract_term'] ?? 'N/A'),
            'Project readiness reasons: ' . implode(', ', (array) ($data['project_readiness_reasons'] ?? [])),
            'Service start timeline: ' . str_replace('_', ' ', (string) ($data['service_start_timeline'] ?? '')),
            'Funds approval status: ' . str_replace('_', ' ', (string) ($data['funds_approval_status'] ?? '')),
            'Budget amount or range: ' . (($data['budget_amount_range'] ?? '') !== '' ? $data['budget_amount_range'] : 'Not provided'),
            'Internal security cost calculated: ' . ucfirst((string) ($data['true_internal_cost_calculated'] ?? 'no')),
            'Service package expectation: ' . str_replace('_', ' ', (string) ($data['service_package_expectation'] ?? '')),
            'Patrol types: ' . $this->implodeOrDefault((array) ($data['patrol_types'] ?? []), 'Not applicable'),
            'Compliance terms: ' . (($data['compliance_terms'] ?? '') !== '' ? $data['compliance_terms'] : 'None provided'),
            'Additional vendor notes: ' . (($data['additional_notes_to_vendors'] ?? '') !== '' ? $data['additional_notes_to_vendors'] : 'None provided'),
            'Known site risks: ' . (($data['known_site_risks'] ?? '') !== '' ? $data['known_site_risks'] : 'None provided'),
        ]));

        $specialRequirements = [
            'Contact: ' . ($data['contact_name'] ?? 'N/A') . ' (' . ($data['contact_job_title'] ?? 'N/A') . ')',
            'Organization: ' . ($data['organization_name'] ?? 'N/A'),
            'Business address: ' . ($data['business_address'] ?? 'N/A'),
            'Contact email: ' . ($data['contact_email'] ?? 'N/A'),
            'Contact phone: ' . ($data['contact_phone'] ?? 'N/A'),
            'Preferred contact method: ' . str_replace('_', ' ', (string) ($data['preferred_contact_method'] ?? '')),
            'Final decision maker: ' . $this->humanizeFlag((string) ($data['final_decision_maker'] ?? '')),
            'Approval authority: ' . $this->humanizeEnum((string) ($data['approval_authority'] ?? '')),
            'Final approver: ' . (($data['final_approver_name'] ?? '') !== '' ? $data['final_approver_name'] : 'Not needed'),
            'Hours/day: ' . ($data['hours_per_day'] ?? 'N/A'),
            'Days/week: ' . ($data['days_per_week'] ?? 'N/A'),
            'Weeks/year: ' . ($data['weeks_per_year'] ?? 'N/A'),
            'Shifts needed: ' . implode(', ', (array) ($data['shifts_needed'] ?? [])),
            'Assignment type: ' . str_replace('_', ' ', (string) ($data['assignment_type'] ?? '')),
            'Patrol types: ' . $this->implodeOrDefault((array) ($data['patrol_types'] ?? []), 'Not applicable'),
            'Services requested: ' . implode(', ', (array) ($data['service_types'] ?? [])),
            'Additional service detail: ' . (($data['service_type_other'] ?? '') !== '' ? $data['service_type_other'] : 'Not provided'),
            'Duties required: ' . implode(', ', (array) ($data['duties_required'] ?? [])),
            'Additional duty detail: ' . (($data['duties_other'] ?? '') !== '' ? $data['duties_other'] : 'Not provided'),
            'Pricing exceeds expectations: ' . $this->implodeOrDefault((array) ($data['if_pricing_exceeds'] ?? []), 'No adjustments selected'),
            'Allow scope adjustment: ' . str_replace('_', ' ', (string) ($data['allow_scope_adjustment'] ?? '')),
            'Cost comparison requested: ' . (($data['cost_comparison_requested'] ?? '') === 'yes' ? 'Yes' : 'No'),
            'Officer licensing required: ' . $this->humanizeEnum((string) ($data['officer_licensing_required'] ?? '')),
            'Background checks required: ' . (($data['background_checks_required'] ?? '') === 'yes' ? 'Yes' : 'No'),
            'Drug testing required: ' . (($data['drug_testing_required'] ?? '') === 'yes' ? 'Yes' : 'No'),
            'Uniformed officers required: ' . (($data['uniformed_officers_required'] ?? '') === 'yes' ? 'Yes' : 'No'),
            'Insurance minimums required: ' . $this->implodeOrDefault((array) ($data['insurance_minimums_required'] ?? []), 'Not provided'),
            'Supporting documents uploaded: ' . (count((array) ($data['supporting_documents'] ?? [])) > 0 ? (string) count((array) $data['supporting_documents']) : 'None'),
            'Vendor response deadline: ' . ($data['vendor_response_deadline'] ?? 'N/A'),
            'Additional notes to vendors: ' . (($data['additional_notes_to_vendors'] ?? '') !== '' ? $data['additional_notes_to_vendors'] : 'None'),
            'Current security setup: ' . str_replace('_', ' ', (string) ($data['current_security_setup'] ?? '')),
            'Replacing provider: ' . (($data['is_replacing_provider'] ?? '') === 'yes' ? 'Yes' : 'No'),
            'Multiple bids required: ' . (($data['multiple_bids_required'] ?? '') === 'yes' ? 'Yes' : 'No'),
            'Willing to adjust scope to budget: ' . (($data['willing_adjust_scope_to_budget'] ?? '') === 'yes' ? 'Yes' : 'No'),
            'Move forward if accepted: ' . $this->humanizeEnum((string) ($data['move_forward_if_accepted'] ?? '')),
            'Risk assessment in last 12 months: ' . str_replace('_', ' ', (string) ($data['risk_assessment_last_12_months'] ?? '')),
        ];
        $data['special_requirements'] = array_values(array_filter($specialRequirements));

        // Remove non-job columns from payload before create.
        $drop = array_merge(self::QUESTIONNAIRE_FIELDS, [
            'budget_approved',
            'ready_to_move_forward',
        ]);
        foreach ($drop as $key) {
            unset($data[$key]);
        }

        JobPosting::create($data);
        return redirect()->route('job-board')->with('success', 'Job posted successfully.');
    }

    public function show(JobPosting $job): View
    {
        $job->load(['user:id,name,company', 'bids.user:id,name,company']);
        return view('jobs.show', compact('job'));
    }

    public function edit(JobPosting $job): View|RedirectResponse
    {
        if ($job->user_id !== auth()->id()) {
            abort(403);
        }
        return view('jobs.edit', compact('job'));
    }

    public function update(StoreJobPostingRequest $request, JobPosting $job): RedirectResponse
    {
        if ($job->user_id !== $request->user()->id) {
            abort(403);
        }

        $data = $request->validated();
        $data['special_requirements'] = $request->filled('special_requirements')
            ? array_filter(array_map('trim', explode("\n", $request->special_requirements)))
            : null;
        $job->update($data);
        return redirect()->route('jobs.show', $job)->with('success', 'Job updated.');
    }

    public function destroy(Request $request, JobPosting $job): RedirectResponse
    {
        if ($job->user_id !== $request->user()->id) {
            abort(403);
        }
        $job->delete();
        return redirect()->route('job-board')->with('success', 'Job removed.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildQuestionnaireData(array $data, Request $request): array
    {
        $snapshot = [];

        foreach (self::QUESTIONNAIRE_FIELDS as $field) {
            if (array_key_exists($field, $data)) {
                $snapshot[$field] = $data[$field];
            }
        }

        $snapshot['phone_verified'] = (bool) $request->user()?->phone_verified;
        $snapshot['captured_at'] = now()->toIso8601String();

        return $snapshot;
    }

    /**
     * @return array<int, array{original_name: string, path: string, mime_type: string|null, size: int|null}>
     */
    private function storeSupportingDocuments(Request $request): array
    {
        $stored = [];

        foreach ((array) $request->file('supporting_documents', []) as $file) {
            if (! $file) {
                continue;
            }

            $path = $file->store('job-postings/supporting-documents/' . $request->user()->id, 'public');

            $stored[] = [
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ];
        }

        return $stored;
    }

    private function humanizeEnum(string $value): string
    {
        if ($value === '') {
            return 'N/A';
        }

        return ucwords(str_replace('_', ' ', $value));
    }

    private function humanizeFlag(string $value): string
    {
        return match ($value) {
            'yes' => 'Yes',
            'no' => 'No',
            'authorized_representative' => 'Authorized representative',
            default => $this->humanizeEnum($value),
        };
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function implodeOrDefault(array $values, string $default): string
    {
        $filtered = array_values(array_filter(array_map(fn ($value) => is_scalar($value) ? (string) $value : null, $values)));

        return $filtered !== [] ? implode(', ', $filtered) : $default;
    }
}
