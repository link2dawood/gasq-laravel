<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJobPostingRequest;
use App\Models\Bid;
use App\Models\JobPosting;
use App\Notifications\HireNotification;
use App\Services\VendorOpportunityManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class JobPostingController extends Controller
{
    private const STARTER_SESSION_KEY = 'job_posting_starter';
    private const PREVIEW_SESSION_KEY = 'job_posting_preview';
    private const ESTIMATOR_PREFILL_SESSION_KEY = 'job_posting_estimator_prefill';
    private const ESTIMATOR_RETURN_SESSION_KEY = 'job_posting_estimator_return_url';

    /**
     * Canonical buyer service options for the starter dropdown + validation.
     * Names come from config/security_services.php (single source of truth,
     * shared with the public /security-services page); 'Other' is appended.
     *
     * @return list<string>
     */
    private static function starterServiceOptions(): array
    {
        $names = array_column(config('security_services.services', []), 'name');
        $names[] = 'Other';

        return $names;
    }

    /**
     * Fields captured from the buyer questionnaire and persisted as a per-posting snapshot.
     *
     * @var list<string>
     */
    private const QUESTIONNAIRE_FIELDS = [
        // Section 1: Contact Information
        'contact_name',
        'contact_job_title',
        'organization_name',
        'contact_email',
        'contact_phone',
        'preferred_contact_method',
        'best_time_to_contact',
        // Section 2: Decision Authority
        'final_decision_maker',
        'approval_authority',
        'final_approver_name',
        'budget_approved_status',
        'move_forward_if_accepted',
        // Section 3: Service Location
        'business_address',
        'multiple_locations',
        'locations_count',
        'property_type_other',
        // Section 4: Service Request Details
        'service_types',
        'service_type_other',
        'request_type',
        'desired_contract_term',
        'primary_reason',
        // Section 5: Scope, Schedule and Staffing
        'hours_per_day',
        'days_per_week',
        'weeks_per_year',
        'staff_per_shift',
        'shifts_needed',
        'patrol_types',
        'armed_status',
        'deployment_types',
        // Section 6: Duties and Site Conditions
        'duties_required',
        'duties_other',
        'service_package_expectation',
        'supporting_documents',
        'known_site_risks',
        'equipment_requirements',
        'uniform_requirements',
        'reporting_requirements',
        // Section 7: Budget and Offer Terms (auto-filled from calculator prefill)
        'hourly_budget',
        'monthly_budget',
        'annual_budget',
        'budget_amount_range',
        'approved_budget_amount',
        'selection_method',
        'offer_price',
        // Section 8: Compliance Requirements
        'insurance_minimums_required',
        'compliance_terms',
        // Section 9: Posting Terms and Submission
        'additional_notes_to_vendors',
        'buyer_certification',
        'consent_to_contact',
    ];

    public function index(Request $request): View
    {
        $user = $request->user();
        // Buyers see only their own posted jobs (this is their "My Jobs" page,
        // not the public vendor board). Vendors/admins/guests see the open board.
        $isBuyerView = $user && $user->isBuyer();

        $query = JobPosting::with(['user:id,name', 'bids' => fn ($q) => $q->with('user:id,name,company')]);

        if ($isBuyerView) {
            $query->where('user_id', $user->id);
        }

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

        // The public board hides expired listings; a buyer should still see all
        // of their own jobs (active, expired and closed) for follow-up.
        if (! $isBuyerView) {
            $query->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
        }

        $jobs = $query->latest()->paginate(15)->withQueryString();

        return view('jobs.index', compact('jobs', 'isBuyerView'));
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
            'starterServiceOptions' => self::starterServiceOptions(),
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
            'annual_budget' => ['nullable', 'numeric', 'min:0'],
            'monthly_budget' => ['nullable', 'numeric', 'min:0'],
            'hourly_budget' => ['nullable', 'numeric', 'min:0'],
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
            'annual_budget' => $data['annual_budget'] ?? null,
            'monthly_budget' => $data['monthly_budget'] ?? null,
            'hourly_budget' => $data['hourly_budget'] ?? null,
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
            'starter_service_type' => ['required', Rule::in(self::starterServiceOptions())],
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

        // Re-open the full buyer questionnaire (same UI as create), pre-filled with
        // the job's saved answers, so buyers can complete or correct every question.
        $q = is_array($job->questionnaire_data) ? $job->questionnaire_data : [];

        $prefill = array_merge($q, array_filter([
            'title' => $job->title,
            'category' => $job->category,
            'location' => $job->location,
            'zip_code' => $job->zip_code,
            'latitude' => $job->latitude,
            'longitude' => $job->longitude,
            'property_type' => $job->property_type,
            'guards_per_shift' => $job->guards_per_shift,
            'budget_min' => $job->budget_min,
            'budget_max' => $job->budget_max,
            'service_start_date' => $job->service_start_date?->format('Y-m-d'),
            'service_end_date' => $job->service_end_date?->format('Y-m-d'),
        ], fn ($v) => $v !== null && $v !== ''));

        $starter = [
            'starter_service_type' => $job->category,
            'service_label' => $q['service_label'] ?? $job->category,
            'category' => $job->category,
            'location' => $job->location,
            'zip_code' => $job->zip_code,
            'latitude' => $job->latitude,
            'longitude' => $job->longitude,
            'google_place_id' => $q['google_place_id'] ?? '',
            'service_types' => $q['service_types'] ?? [],
        ];

        return view('jobs.create', [
            'starter' => $starter,
            'prefill' => $prefill,
            'showDetailsStep' => true,
            'starterServiceOptions' => self::starterServiceOptions(),
            'editingJob' => $job,
        ]);
    }

    public function update(StoreJobPostingRequest $request, JobPosting $job, VendorOpportunityManager $vendorOpportunityManager): RedirectResponse
    {
        if ($job->user_id !== $request->user()->id) {
            abort(403);
        }

        $data = $request->validated();
        $data['special_requirements'] = $request->filled('special_requirements')
            ? array_filter(array_map('trim', explode("\n", $request->special_requirements)))
            : null;

        if ($request->hasFile('supporting_documents')) {
            $data['supporting_documents'] = $this->storeSupportingDocuments($request);
        }

        // Rebuild the full payload (budgets + questionnaire snapshot) exactly like
        // a new post, but preserve the original owner, lifecycle status and any
        // previously-uploaded documents.
        $payload = $this->buildJobPostingPayload($data, $request);
        unset($payload['user_id'], $payload['status']);
        if (! \Illuminate\Support\Facades\Schema::hasColumn('job_postings', 'questionnaire_data')) {
            unset($payload['questionnaire_data']);
        }
        if (! $request->hasFile('supporting_documents')) {
            unset($payload['supporting_documents']);
        }

        $job->update($payload);

        // Re-qualify: a corrected questionnaire can move the job out of
        // "Pending Qualification" (Tier C) and release it to vendors.
        $vendorOpportunityManager->createForPublishedJob($job);

        return redirect()->route('jobs.show', $job)
            ->with('success', 'Job updated. Your questionnaire has been re-checked.');
    }

    public function hire(Request $request, JobPosting $job): RedirectResponse
    {
        if ($job->user_id !== $request->user()->id) {
            abort(403);
        }
        if ($job->isHired()) {
            return back()->with('error', 'A vendor has already been hired for this job.');
        }

        $data = $request->validate([
            'bid_id' => ['nullable', 'integer', 'exists:bids,id'],
            'external_name' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'in:platform,external,none'],
        ]);

        $source = $data['source'] ?? ($data['bid_id'] ?? null ? 'platform' : 'external');

        if ($source === 'none') {
            return $this->close($request->merge(['close_reason' => $request->input('close_reason', 'still_deciding')]), $job);
        }

        $hiredBid = null;
        if ($source === 'platform') {
            if (empty($data['bid_id'])) {
                return back()->with('error', 'Pick which vendor you hired.');
            }
            $hiredBid = Bid::with('user')->findOrFail($data['bid_id']);
            if ($hiredBid->job_posting_id !== $job->id) {
                abort(403);
            }
        } elseif ($source === 'external' && empty($data['external_name'])) {
            return back()->with('error', 'Tell us who you hired.');
        }

        DB::transaction(function () use ($job, $hiredBid, $data, $source) {
            $now = now();

            if ($hiredBid) {
                $hiredBid->update([
                    'status' => 'accepted',
                    'hired_at' => $now,
                    'responded_at' => $hiredBid->responded_at ?? $now,
                ]);

                Bid::where('job_posting_id', $job->id)
                    ->where('id', '!=', $hiredBid->id)
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'rejected',
                        'responded_at' => $now,
                    ]);
            }

            // Freeze the contract value: the hired bid amount when present,
            // otherwise the job's budget estimate. Powers the admin dashboard.
            $awardedValue = ($hiredBid && (float) $hiredBid->amount > 0)
                ? (float) $hiredBid->amount
                : ($job->budget_max ?? $job->budget_min);

            $job->update([
                'hired_bid_id' => $hiredBid?->id,
                'hired_at' => $now,
                'hired_external_name' => $source === 'external' ? $data['external_name'] : null,
                'awarded_value' => $awardedValue,
                'status' => 'awarded',
                'last_activity_at' => $now,
            ]);
        });

        $job->refresh()->load('hiredBid.user', 'user', 'bids.user');

        $job->user->notify(new HireNotification($job, 'buyer'));
        if ($hiredBid) {
            $hiredBid->user->notify(new HireNotification($job, 'vendor'));
            foreach ($job->bids as $other) {
                if ($other->id !== $hiredBid->id) {
                    $other->user?->notify(new HireNotification($job, 'other_vendors'));
                }
            }
        }

        return redirect()->route('jobs.show', $job)->with('success', 'Hire recorded. Other bids were rejected and notifications sent.');
    }

    public function close(Request $request, JobPosting $job): RedirectResponse
    {
        if ($job->user_id !== $request->user()->id) {
            abort(403);
        }

        $data = $request->validate([
            'close_reason' => ['required', 'in:still_deciding,diy_or_friend,change_of_plan,on_hold,quotes_not_right,other'],
            'close_reason_other' => ['nullable', 'string', 'max:500', 'required_if:close_reason,other'],
        ]);

        $job->update([
            'status' => 'closed',
            'closed_at' => now(),
            'close_reason' => $data['close_reason'],
            'close_reason_other' => $data['close_reason_other'] ?? null,
            'last_activity_at' => now(),
        ]);

        return redirect()->route('jobs.show', $job)->with('success', 'Job closed. Thanks for the feedback.');
    }

    /**
     * Persist the buyer-side workflow checklist (interviews scheduled/completed,
     * risk assessment scheduled/completed, final verifications, offer status).
     * Called from the Active Job Offer Summary card on the buyer dashboard.
     */
    public function updateWorkflowStatus(Request $request, JobPosting $job): RedirectResponse
    {
        if ($job->user_id !== $request->user()->id) {
            abort(403);
        }

        $data = $request->validate([
            'offer_status' => ['nullable', 'in:open,hired,closed_no_hire'],
            'interviews_scheduled' => ['nullable', 'in:0,1,yes,no'],
            'interviews_completed' => ['nullable', 'in:0,1,yes,no'],
            'risk_assessment_scheduled' => ['nullable', 'in:0,1,yes,no'],
            'risk_assessment_completed' => ['nullable', 'in:0,1,yes,no'],
            'final_verifications_complete' => ['nullable', 'in:0,1,yes,no'],
            'hired_bid_id' => ['nullable', 'integer', 'exists:bids,id'],
        ]);

        $toBool = fn ($v) => in_array((string) $v, ['1', 'yes'], true);

        $payload = [];
        foreach ([
            'interviews_scheduled',
            'interviews_completed',
            'risk_assessment_scheduled',
            'risk_assessment_completed',
            'final_verifications_complete',
        ] as $f) {
            if ($request->has($f)) {
                $payload[$f] = $toBool($data[$f] ?? null);
            }
        }

        if (! empty($data['offer_status'])) {
            $payload['offer_status'] = $data['offer_status'];
            if ($data['offer_status'] === 'hired' && ! empty($data['hired_bid_id'])) {
                $payload['hired_bid_id'] = $data['hired_bid_id'];
                $payload['hired_at'] = now();
                $payload['status'] = 'hired';
            } elseif ($data['offer_status'] === 'closed_no_hire') {
                $payload['status'] = 'closed';
                $payload['closed_at'] = now();
                $payload['close_reason'] = 'no_hire';
            }
        }

        $payload['last_activity_at'] = now();
        $job->update($payload);

        return back()->with('success', 'Job status updated.');
    }

    public function bidsFragment(Request $request, JobPosting $job): JsonResponse
    {
        $job->load(['bids.user:id,name,company', 'hiredBid.user:id,name,company']);

        $isOwner = $request->user()?->id === $job->user_id;

        $bids = $job->bids->map(function (Bid $bid) {
            return [
                'id' => $bid->id,
                'vendor_id' => $bid->user_id,
                'vendor_name' => $bid->user?->name,
                'vendor_company' => $bid->user?->company,
                'amount' => (float) $bid->amount,
                'status' => $bid->status,
                'vendor_response_status' => $bid->vendor_response_status,
                'message' => $bid->message,
                'counter_offer_amount' => $bid->counter_offer_amount ? (float) $bid->counter_offer_amount : null,
                'counter_offer_message' => $bid->counter_offer_message,
                'counter_offer_at' => $bid->counter_offer_at?->toIso8601String(),
                'vendor_responded_at' => $bid->vendor_responded_at?->toIso8601String(),
                'hired_at' => $bid->hired_at?->toIso8601String(),
                'is_hired' => $job->hired_bid_id === $bid->id,
            ];
        })->values();

        return response()->json([
            'job' => [
                'id' => $job->id,
                'status' => $job->status,
                'hired_bid_id' => $job->hired_bid_id,
                'hired_external_name' => $job->hired_external_name,
                'hired_at' => $job->hired_at?->toIso8601String(),
                'closed_at' => $job->closed_at?->toIso8601String(),
                'is_hired' => $job->isHired(),
                'is_closed' => $job->isClosed(),
                'is_owner' => $isOwner,
            ],
            'counts' => [
                'total' => $job->bids->count(),
                'responded' => $job->bids->filter(fn ($b) => $b->hasVendorResponded())->count(),
                'accepted' => $job->bids->filter(fn ($b) => $b->vendorAccepted())->count(),
                'declined' => $job->bids->filter(fn ($b) => $b->vendorDeclined())->count(),
            ],
            'bids' => $bids,
        ]);
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

        // Pull calculator-derived budget values from the estimator prefill so
        // the buyer never has to re-enter them on the questionnaire.
        $prefill = $this->estimatorPrefillSessionData();
        foreach (['annual_budget', 'monthly_budget', 'hourly_budget', 'budget_amount_range'] as $budgetKey) {
            if (! isset($payload[$budgetKey]) && isset($prefill[$budgetKey])) {
                $payload[$budgetKey] = $prefill[$budgetKey];
            }
        }

        $annualBudget = is_numeric($payload['annual_budget'] ?? null) ? (float) $payload['annual_budget'] : 0.0;
        $monthlyBudget = is_numeric($payload['monthly_budget'] ?? null) ? (float) $payload['monthly_budget'] : 0.0;
        $hourlyBudget = is_numeric($payload['hourly_budget'] ?? null) ? (float) $payload['hourly_budget'] : 0.0;

        // Fallback: if the buyer skipped the calculator and didn't provide any
        // budget figure, derive one from the scope using the same outsourcing
        // formula the Instant Estimator uses. Without this the Tier-A lead
        // would show "Contract Value: —" on the dashboard and the email.
        if ($annualBudget <= 0 && $monthlyBudget <= 0 && $hourlyBudget <= 0
            && is_numeric($payload['hours_per_day'] ?? null)
            && is_numeric($payload['days_per_week'] ?? null)
            && is_numeric($payload['weeks_per_year'] ?? null)) {
            $baselineWage = $this->defaultBaselineWageForJob($payload);
            $employerCost = $baselineWage > 0 ? $baselineWage / 0.70 : 0.0;
            $annualEmployerCost = $employerCost * 3744;
            $internalTrueHourly = $annualEmployerCost > 0 ? $annualEmployerCost / 1456 : 0.0;
            $outsourcedHourly = $internalTrueHourly * 0.70;
            $staffPerShift = is_numeric($payload['staff_per_shift'] ?? null) ? max(1.0, (float) $payload['staff_per_shift']) : 1.0;
            $weeklyCoverageHours = (float) $payload['hours_per_day'] * (float) $payload['days_per_week'] * $staffPerShift;
            $weeksPerYear = max(1.0, (float) $payload['weeks_per_year']);
            $annualCoverageHours = $weeklyCoverageHours * 52;
            $termCoverageHours = $weeklyCoverageHours * $weeksPerYear;
            $hourlyBudget = round($outsourcedHourly, 2);
            $annualBudget = round($outsourcedHourly * $annualCoverageHours, 2);
            $monthlyBudget = round($annualBudget / 12, 2);
            $termBudget = round($outsourcedHourly * $termCoverageHours, 2);
            $payload['hourly_budget'] = $hourlyBudget;
            $payload['monthly_budget'] = $monthlyBudget;
            $payload['annual_budget'] = $annualBudget;
            if (empty($payload['budget_amount_range']) && $termBudget > 0) {
                $payload['budget_amount_range'] = '$' . number_format($termBudget, 2);
            }
        }

        if ($annualBudget > 0) {
            $payload['budget_min'] = $annualBudget;
            $payload['budget_max'] = $annualBudget;
        } elseif ($monthlyBudget > 0) {
            $payload['budget_min'] = $monthlyBudget * 12;
            $payload['budget_max'] = $monthlyBudget * 12;
        } elseif ($hourlyBudget > 0 && is_numeric($payload['hours_per_day'] ?? null)
            && is_numeric($payload['days_per_week'] ?? null)
            && is_numeric($payload['weeks_per_year'] ?? null)) {
            $annualHours = (float) $payload['hours_per_day'] * (float) $payload['days_per_week'] * (float) $payload['weeks_per_year'];
            if ($annualHours > 0) {
                $payload['budget_min'] = $hourlyBudget * $annualHours;
                $payload['budget_max'] = $hourlyBudget * $annualHours;
            }
        }

        $payload['questionnaire_data'] = $this->buildQuestionnaireData($payload, $request);
        $payload['status'] = 'open';

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
            'Hours/day: ' . ($payload['hours_per_day'] ?? 'N/A'),
            'Days/week: ' . ($payload['days_per_week'] ?? 'N/A'),
            'Weeks/year: ' . ($payload['weeks_per_year'] ?? 'N/A'),
            'Patrol types: ' . $this->implodeOrDefault((array) ($payload['patrol_types'] ?? []), 'Not applicable'),
            'Services requested: ' . implode(', ', (array) ($payload['service_types'] ?? [])),
            'Additional service detail: ' . (($payload['service_type_other'] ?? '') !== '' ? $payload['service_type_other'] : 'Not provided'),
            'Duties required: ' . implode(', ', (array) ($payload['duties_required'] ?? [])),
            'Additional duty detail: ' . (($payload['duties_other'] ?? '') !== '' ? $payload['duties_other'] : 'Not provided'),
            'Insurance minimums required: ' . $this->implodeOrDefault((array) ($payload['insurance_minimums_required'] ?? []), 'Not provided'),
            'Supporting documents uploaded: ' . (count((array) ($payload['supporting_documents'] ?? [])) > 0 ? (string) count((array) $payload['supporting_documents']) : 'None'),
            'Additional notes to vendors: ' . (($payload['additional_notes_to_vendors'] ?? '') !== '' ? $payload['additional_notes_to_vendors'] : 'None'),
            'Replacing provider: ' . (($payload['is_replacing_provider'] ?? '') === 'yes' ? 'Yes' : 'No'),
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
     * Pick a sensible default hourly baseline wage based on the job's service
     * type / category. Mirrors the per-service defaults the Instant Estimator
     * shows so the auto-computed bid offer lines up with what a buyer would
     * have seen had they routed through the calculator.
     *
     * @param  array<string, mixed>  $payload
     */
    private function defaultBaselineWageForJob(array $payload): float
    {
        $serviceDefaults = [
            'unarmed' => 33.0,
            'armed' => 46.0,
            'supervisor' => 46.0,
            'mobile' => 46.0,
            'mobile_patrol' => 46.0,
            'patrol' => 46.0,
            'loss' => 46.0,
            'executive' => 60.0,
            'offduty' => 60.0,
            'off_duty' => 60.0,
            'off duty police officer' => 60.0,
            'roving patrol' => 46.0,
            'guards' => 33.0,
        ];

        $candidates = [];
        $candidates[] = strtolower((string) ($payload['service_type'] ?? ''));
        $candidates[] = strtolower((string) ($payload['category'] ?? ''));
        foreach ((array) ($payload['service_types'] ?? []) as $value) {
            if (is_string($value)) {
                $candidates[] = strtolower($value);
            }
        }

        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);
            if ($candidate === '') continue;
            if (isset($serviceDefaults[$candidate])) {
                return $serviceDefaults[$candidate];
            }
            foreach ($serviceDefaults as $key => $rate) {
                if (str_contains($candidate, $key)) {
                    return $rate;
                }
            }
        }

        return 33.0;
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
        // Map estimator service codes to the canonical buyer service labels
        // (config/security_services.php) so a job prefilled from the estimator
        // uses the same wording as the rest of the site.
        return match ($serviceType) {
            'unarmed' => 'Unarmed Security Guard',
            'armed' => 'Armed Security Guard',
            'mobile' => 'Mobile Patrol',
            'loss' => 'Loss Prevention',
            'executive' => 'Executive Protection',
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
