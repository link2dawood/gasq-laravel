<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJobPostingRequest;
use App\Models\JobPosting;
use App\Services\VendorOpportunityManager;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

class JobPostingController extends Controller
{
    private const STARTER_SESSION_KEY = 'job_posting_starter';
    private const PREVIEW_SESSION_KEY = 'job_posting_preview';
    private const ESTIMATOR_PREFILL_SESSION_KEY = 'job_posting_estimator_prefill';
    private const ESTIMATOR_RETURN_SESSION_KEY = 'job_posting_estimator_return_url';

    /**
     * @var list<string>
     */
    private const STARTER_SERVICE_OPTIONS = [
        'Unarmed Security Guard',
        'Armed Security Guard',
        'Mobile Patrol',
        'Foot Patrol',
        'Roving Patrol',
        'Concierge / Front Desk Security',
        'Access Control',
        'Fire Watch',
        'Loss Prevention',
        'Event Security',
        'Parking Enforcement',
        'Other',
    ];

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

        $starter = $this->starterSessionData();
        $showDetailsStep = request()->query('step') === 'details' && $starter !== [];

        return view('jobs.create', [
            'starter' => $starter,
            'prefill' => $this->estimatorPrefillSessionData(),
            'showDetailsStep' => $showDetailsStep,
            'starterServiceOptions' => self::STARTER_SERVICE_OPTIONS,
        ]);
    }

    public function prepareFromEstimator(Request $request): \Illuminate\Http\JsonResponse
    {
        if (! $request->user()?->isBuyer()) {
            return response()->json([
                'message' => 'Only buyers can use the post-job path from the instant estimator.',
            ], 403);
        }

        $data = $request->validate([
            'service_type' => ['required', 'string', 'max:60'],
            'location' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_job_title' => ['nullable', 'string', 'max:255'],
            'organization_name' => ['nullable', 'string', 'max:255'],
            'property_site_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'business_address' => ['nullable', 'string', 'max:500'],
            'final_decision_maker' => ['nullable', 'string', 'max:50'],
            'funds_approval_status' => ['nullable', 'string', 'max:50'],
            'move_forward_if_accepted' => ['nullable', 'string', 'max:50'],
            'property_type' => ['nullable', 'string', 'max:100'],
            'current_security_setup' => ['nullable', 'string', 'max:50'],
            'service_start_timeline' => ['nullable', 'string', 'max:60'],
            'primary_reason' => ['nullable', 'string', 'max:4000'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'budget_amount_range' => ['nullable', 'string', 'max:255'],
            'hours_per_day' => ['nullable', 'numeric', 'min:1', 'max:24'],
            'days_per_week' => ['nullable', 'integer', 'min:1', 'max:7'],
            'weeks_per_year' => ['nullable', 'integer', 'min:1', 'max:53'],
            'guards_per_shift' => ['nullable', 'integer', 'min:1', 'max:255'],
            'cost_comparison_requested' => ['nullable', 'in:yes,no'],
        ]);

        $starter = $this->buildStarterSessionData([
            'starter_service_type' => $this->estimatorStarterServiceType((string) $data['service_type']),
            'starter_service_type_other' => $this->estimatorStarterServiceOther((string) $data['service_type']),
            'location' => $data['location'],
        ]);

        $prefill = array_filter([
            'title' => $data['title'] ?: $starter['title'],
            'contact_name' => $data['contact_name'] ?? null,
            'contact_job_title' => $data['contact_job_title'] ?? null,
            'organization_name' => $data['organization_name'] ?? null,
            'property_site_name' => $data['property_site_name'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'business_address' => $data['business_address'] ?? $data['location'],
            'final_decision_maker' => $data['final_decision_maker'] ?? null,
            'funds_approval_status' => $data['funds_approval_status'] ?? null,
            'move_forward_if_accepted' => $data['move_forward_if_accepted'] ?? 'yes',
            'property_type' => $data['property_type'] ?? null,
            'current_security_setup' => $data['current_security_setup'] ?? null,
            'service_start_timeline' => $data['service_start_timeline'] ?? null,
            'primary_reason' => $data['primary_reason'] ?? $data['notes'] ?? null,
            'budget_amount_range' => $data['budget_amount_range'] ?? null,
            'hours_per_day' => $data['hours_per_day'] ?? null,
            'days_per_week' => $data['days_per_week'] ?? null,
            'weeks_per_year' => $data['weeks_per_year'] ?? null,
            'guards_per_shift' => $data['guards_per_shift'] ?? null,
            'cost_comparison_requested' => $data['cost_comparison_requested'] ?? null,
            'location' => $starter['location'],
            'zip_code' => $starter['zip_code'],
            'latitude' => $starter['latitude'],
            'longitude' => $starter['longitude'],
            'google_place_id' => $starter['google_place_id'],
        ], static fn ($value) => $value !== null && $value !== '');

        $request->session()->put(self::STARTER_SESSION_KEY, $starter);
        $request->session()->put(self::ESTIMATOR_PREFILL_SESSION_KEY, $prefill);
        $request->session()->put(
            self::ESTIMATOR_RETURN_SESSION_KEY,
            route('instant-estimator.index', ['post_job' => 'success'])
        );

        return response()->json([
            'ok' => true,
            'message' => 'Job draft prepared. You can continue to the buyer questionnaire with your estimate data prefilled.',
            'job_create_url' => route('jobs.create', ['step' => 'details']),
        ]);
    }

    public function start(Request $request): RedirectResponse
    {
        if (! $request->user()?->isBuyer()) {
            return redirect()->route('job-board')->with('error', 'Only buyers can post jobs.');
        }

        $data = $request->validate([
            'starter_service_type' => ['required', Rule::in(self::STARTER_SERVICE_OPTIONS)],
            'starter_service_type_other' => ['nullable', 'required_if:starter_service_type,Other', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'google_place_id' => ['nullable', 'string', 'max:255'],
        ]);

        $request->session()->put(self::STARTER_SESSION_KEY, $this->buildStarterSessionData($data));

        return redirect()->route('jobs.create', ['step' => 'details']);
    }

    public function preview(StoreJobPostingRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['supporting_documents'] = $this->storeSupportingDocuments($request);

        $request->session()->put(self::PREVIEW_SESSION_KEY, [
            'form_data' => $validated,
            'payload' => $this->buildJobPostingPayload($validated, $request),
        ]);

        return redirect()->route('jobs.review');
    }

    public function review(Request $request): View|RedirectResponse
    {
        if (! $request->user()?->isBuyer()) {
            return redirect()->route('job-board')->with('error', 'Only buyers can post jobs.');
        }

        $preview = $this->previewSessionData();

        if ($preview === []) {
            return redirect()->route('jobs.create', ['step' => 'details'])
                ->with('error', 'Complete the questionnaire so we can generate your job announcement.');
        }

        return view('jobs.review', [
            'preview' => $preview['payload'],
            'questionnaire' => $preview['payload']['questionnaire_data'] ?? [],
        ]);
    }

    public function editReview(Request $request): RedirectResponse
    {
        $preview = $this->previewSessionData();

        if ($preview === []) {
            return redirect()->route('jobs.create', ['step' => 'details']);
        }

        return redirect()->route('jobs.create', ['step' => 'details'])
            ->withInput($preview['form_data'] ?? []);
    }

    public function publish(Request $request, VendorOpportunityManager $vendorOpportunityManager): RedirectResponse
    {
        $preview = $this->previewSessionData();
        $estimatorReturnUrl = session(self::ESTIMATOR_RETURN_SESSION_KEY);

        if ($preview === [] || ! isset($preview['payload']) || ! is_array($preview['payload'])) {
            return redirect()->route('jobs.create', ['step' => 'details'])
                ->with('error', 'There is no generated job announcement ready to publish yet.');
        }

        $payload = $preview['payload'];

        // If questionnaire_data column does not exist in production yet, strip it rather than crash.
        if (! \Illuminate\Support\Facades\Schema::hasColumn('job_postings', 'questionnaire_data')) {
            unset($payload['questionnaire_data']);
        }

        try {
            $job = JobPosting::create($payload);
            $vendorOpportunityManager->createForPublishedJob($job);
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('jobs.review')
                ->with('error', 'There was a problem saving your job announcement. Please try again or contact support.');
        }

        $this->clearDraftSessions($request);

        if (is_string($estimatorReturnUrl) && $estimatorReturnUrl !== '') {
            return redirect()->to($estimatorReturnUrl)
                ->with('success', 'Job announcement published successfully. Your estimate results are now unlocked.');
        }

        return redirect()->route('jobs.show', $job)
            ->with('success', 'Job announcement published successfully.');
    }

    public function store(StoreJobPostingRequest $request, VendorOpportunityManager $vendorOpportunityManager): RedirectResponse
    {
        $data = $request->validated();
        $data['supporting_documents'] = $this->storeSupportingDocuments($request);

        $job = JobPosting::create($this->buildJobPostingPayload($data, $request));
        $vendorOpportunityManager->createForPublishedJob($job);
        $this->clearDraftSessions($request);
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
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildJobPostingPayload(array $data, Request $request): array
    {
        $payload = $data;
        $payload['user_id'] = $request->user()->id;
        $payload['questionnaire_data'] = $this->buildQuestionnaireData($payload, $request);
        $payload['status'] = 'open';

        $budgetFormat = (string) ($payload['budget_format'] ?? '');
        if ($budgetFormat === 'hourly_budget') {
            $payload['budget_min'] = $payload['hourly_budget'] ?? null;
            $payload['budget_max'] = $payload['hourly_budget'] ?? null;
        } elseif ($budgetFormat === 'monthly_budget') {
            $payload['budget_min'] = $payload['monthly_budget'] ?? null;
            $payload['budget_max'] = $payload['monthly_budget'] ?? null;
        } elseif ($budgetFormat === 'annual_budget') {
            $payload['budget_min'] = $payload['annual_budget'] ?? null;
            $payload['budget_max'] = $payload['annual_budget'] ?? null;
        }

        $payload['description'] = implode("\n", array_filter([
            'Primary reason: ' . ($payload['primary_reason'] ?? 'N/A'),
            'Property / site name: ' . ($payload['property_site_name'] ?? 'N/A'),
            'Property type detail: ' . (($payload['property_type_other'] ?? '') !== '' ? $payload['property_type_other'] : 'Not provided'),
            'Request type: ' . str_replace('_', ' ', (string) ($payload['request_type'] ?? '')),
            'Contract term: ' . ($payload['desired_contract_term'] ?? 'N/A'),
            'Project readiness reasons: ' . implode(', ', (array) ($payload['project_readiness_reasons'] ?? [])),
            'Service start timeline: ' . str_replace('_', ' ', (string) ($payload['service_start_timeline'] ?? '')),
            'Funds approval status: ' . str_replace('_', ' ', (string) ($payload['funds_approval_status'] ?? '')),
            'Budget amount or range: ' . (($payload['budget_amount_range'] ?? '') !== '' ? $payload['budget_amount_range'] : 'Not provided'),
            'Internal security cost calculated: ' . ucfirst((string) ($payload['true_internal_cost_calculated'] ?? 'no')),
            'Service package expectation: ' . str_replace('_', ' ', (string) ($payload['service_package_expectation'] ?? '')),
            'Patrol types: ' . $this->implodeOrDefault((array) ($payload['patrol_types'] ?? []), 'Not applicable'),
            'Compliance terms: ' . (($payload['compliance_terms'] ?? '') !== '' ? $payload['compliance_terms'] : 'None provided'),
            'Additional vendor notes: ' . (($payload['additional_notes_to_vendors'] ?? '') !== '' ? $payload['additional_notes_to_vendors'] : 'None provided'),
            'Known site risks: ' . (($payload['known_site_risks'] ?? '') !== '' ? $payload['known_site_risks'] : 'None provided'),
        ]));

        $specialRequirements = [
            'Contact: ' . ($payload['contact_name'] ?? 'N/A') . ' (' . ($payload['contact_job_title'] ?? 'N/A') . ')',
            'Organization: ' . ($payload['organization_name'] ?? 'N/A'),
            'Business address: ' . ($payload['business_address'] ?? 'N/A'),
            'Contact email: ' . ($payload['contact_email'] ?? 'N/A'),
            'Contact phone: ' . ($payload['contact_phone'] ?? 'N/A'),
            'Preferred contact method: ' . str_replace('_', ' ', (string) ($payload['preferred_contact_method'] ?? '')),
            'Final decision maker: ' . $this->humanizeFlag((string) ($payload['final_decision_maker'] ?? '')),
            'Approval authority: ' . $this->humanizeEnum((string) ($payload['approval_authority'] ?? '')),
            'Final approver: ' . (($payload['final_approver_name'] ?? '') !== '' ? $payload['final_approver_name'] : 'Not needed'),
            'Hours/day: ' . ($payload['hours_per_day'] ?? 'N/A'),
            'Days/week: ' . ($payload['days_per_week'] ?? 'N/A'),
            'Weeks/year: ' . ($payload['weeks_per_year'] ?? 'N/A'),
            'Shifts needed: ' . implode(', ', (array) ($payload['shifts_needed'] ?? [])),
            'Assignment type: ' . str_replace('_', ' ', (string) ($payload['assignment_type'] ?? '')),
            'Patrol types: ' . $this->implodeOrDefault((array) ($payload['patrol_types'] ?? []), 'Not applicable'),
            'Services requested: ' . implode(', ', (array) ($payload['service_types'] ?? [])),
            'Additional service detail: ' . (($payload['service_type_other'] ?? '') !== '' ? $payload['service_type_other'] : 'Not provided'),
            'Duties required: ' . implode(', ', (array) ($payload['duties_required'] ?? [])),
            'Additional duty detail: ' . (($payload['duties_other'] ?? '') !== '' ? $payload['duties_other'] : 'Not provided'),
            'Pricing exceeds expectations: ' . $this->implodeOrDefault((array) ($payload['if_pricing_exceeds'] ?? []), 'No adjustments selected'),
            'Allow scope adjustment: ' . str_replace('_', ' ', (string) ($payload['allow_scope_adjustment'] ?? '')),
            'Cost comparison requested: ' . (($payload['cost_comparison_requested'] ?? '') === 'yes' ? 'Yes' : 'No'),
            'Officer licensing required: ' . $this->humanizeEnum((string) ($payload['officer_licensing_required'] ?? '')),
            'Background checks required: ' . (($payload['background_checks_required'] ?? '') === 'yes' ? 'Yes' : 'No'),
            'Drug testing required: ' . (($payload['drug_testing_required'] ?? '') === 'yes' ? 'Yes' : 'No'),
            'Uniformed officers required: ' . (($payload['uniformed_officers_required'] ?? '') === 'yes' ? 'Yes' : 'No'),
            'Insurance minimums required: ' . $this->implodeOrDefault((array) ($payload['insurance_minimums_required'] ?? []), 'Not provided'),
            'Supporting documents uploaded: ' . (count((array) ($payload['supporting_documents'] ?? [])) > 0 ? (string) count((array) $payload['supporting_documents']) : 'None'),
            'Vendor response deadline: ' . ($payload['vendor_response_deadline'] ?? 'N/A'),
            'Additional notes to vendors: ' . (($payload['additional_notes_to_vendors'] ?? '') !== '' ? $payload['additional_notes_to_vendors'] : 'None'),
            'Current security setup: ' . str_replace('_', ' ', (string) ($payload['current_security_setup'] ?? '')),
            'Replacing provider: ' . (($payload['is_replacing_provider'] ?? '') === 'yes' ? 'Yes' : 'No'),
            'Multiple bids required: ' . (($payload['multiple_bids_required'] ?? '') === 'yes' ? 'Yes' : 'No'),
            'Willing to adjust scope to budget: ' . (($payload['willing_adjust_scope_to_budget'] ?? '') === 'yes' ? 'Yes' : 'No'),
            'Move forward if accepted: ' . $this->humanizeEnum((string) ($payload['move_forward_if_accepted'] ?? '')),
            'Risk assessment in last 12 months: ' . str_replace('_', ' ', (string) ($payload['risk_assessment_last_12_months'] ?? '')),
        ];
        $payload['special_requirements'] = array_values(array_filter($specialRequirements));

        $drop = array_merge(self::QUESTIONNAIRE_FIELDS, [
            'budget_approved',
            'ready_to_move_forward',
        ]);
        foreach ($drop as $key) {
            unset($payload[$key]);
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildStarterSessionData(array $data): array
    {
        $starterServiceType = (string) ($data['starter_service_type'] ?? '');
        $starterServiceOther = trim((string) ($data['starter_service_type_other'] ?? ''));
        $serviceLabel = $starterServiceType === 'Other' && $starterServiceOther !== ''
            ? $starterServiceOther
            : $starterServiceType;

        return [
            'starter_service_type' => $starterServiceType,
            'starter_service_type_other' => $starterServiceOther,
            'service_label' => $serviceLabel,
            'service_types' => [$starterServiceType],
            'service_type_other' => $starterServiceType === 'Other' ? $starterServiceOther : null,
            'category' => substr($serviceLabel, 0, 100),
            'title' => $this->suggestPostingTitle($serviceLabel, (string) ($data['location'] ?? '')),
            'location' => (string) ($data['location'] ?? ''),
            'zip_code' => (string) ($data['zip_code'] ?? ''),
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'google_place_id' => (string) ($data['google_place_id'] ?? ''),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function starterSessionData(): array
    {
        $starter = session(self::STARTER_SESSION_KEY, []);

        return is_array($starter) ? $starter : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function estimatorPrefillSessionData(): array
    {
        $prefill = session(self::ESTIMATOR_PREFILL_SESSION_KEY, []);

        return is_array($prefill) ? $prefill : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function previewSessionData(): array
    {
        $preview = session(self::PREVIEW_SESSION_KEY, []);

        return is_array($preview) ? $preview : [];
    }

    private function clearDraftSessions(Request $request): void
    {
        $request->session()->forget([
            self::STARTER_SESSION_KEY,
            self::PREVIEW_SESSION_KEY,
            self::ESTIMATOR_PREFILL_SESSION_KEY,
            self::ESTIMATOR_RETURN_SESSION_KEY,
        ]);
    }

    private function estimatorStarterServiceType(string $serviceType): string
    {
        return match ($serviceType) {
            'unarmed' => 'Unarmed Security Guard',
            'armed' => 'Armed Security Guard',
            'mobile' => 'Mobile Patrol',
            'loss' => 'Loss Prevention',
            default => 'Other',
        };
    }

    private function estimatorStarterServiceOther(string $serviceType): ?string
    {
        return match ($serviceType) {
            'supervisor' => 'Security Site Supervisor',
            'executive' => 'Executive Protection Agent',
            'offduty' => 'Off Duty Police Officer',
            default => null,
        };
    }

    private function suggestPostingTitle(string $serviceLabel, string $location): string
    {
        $service = trim($serviceLabel);
        $place = trim($location);

        if ($service === '' && $place === '') {
            return '';
        }

        if ($service === '') {
            return 'Security services request for ' . $place;
        }

        if ($place === '') {
            return $service . ' request';
        }

        return $service . ' request for ' . $place;
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
