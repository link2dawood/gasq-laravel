<?php

namespace App\Http\Controllers;

use App\Jobs\SendVendorEstimateDelivery;
use App\Models\VendorBuyerLead;
use App\Models\VendorEstimateDelivery;
use App\Models\VendorLeadEstimate;
use App\Models\VendorPdfAccessEvent;
use App\Models\VendorReviewAppointment;
use App\Services\BudgetSummaryService;
use App\Services\ReportService;
use App\Services\VendorEstimateDeliveryService;
use App\Support\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\HeaderUtils;

class VendorLeadEstimateController extends Controller
{
    public function index(Request $request)
    {
        $vendor = $this->vendor($request);
        $filters = $request->validate(['status' => ['nullable', Rule::in(array_keys(VendorLeadEstimate::STATUSES))], 'lead' => ['nullable', 'integer', 'min:1']]);
        $estimates = VendorLeadEstimate::where('vendor_id', $vendor->id)->with(['lead', 'latestDelivery'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['lead'] ?? null, fn ($query, $lead) => $query->where('vendor_buyer_lead_id', $lead))->latest()->latest('id')->paginate(15)->withQueryString();
        $leadCounts = VendorBuyerLead::where('vendor_id', $vendor->id)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $estimateCounts = VendorLeadEstimate::where('vendor_id', $vendor->id)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('vendor-estimates.index', compact('estimates', 'leadCounts', 'estimateCounts'));
    }

    public function create(Request $request, VendorBuyerLead $lead)
    {
        $vendor = $this->vendor($request);
        abort_unless($lead->vendor_id === $vendor->id, 404);
        $currency = $this->currency($vendor);
        $estimate = new VendorLeadEstimate([
            'title' => 'Security services estimate', 'scope' => $lead->scope, 'weekly_hours' => $lead->weekly_hours,
            'weeks_per_year' => 52, 'one_time_cost' => 0, 'staff_required' => null, 'valid_until' => now()->addDays(30), 'status' => 'draft',
            'currency' => $currency,
        ]);
        // Arriving from the Know Before You Bid Calculator: fill the pricing from
        // the scenario the vendor just saved there, so the figures are not
        // retyped by hand. Nothing is stored until the vendor saves the estimate.
        $budget = $request->boolean('from_budget') ? $this->budgetFill($vendor, $currency) : null;
        if ($budget) {
            $estimate->fill($budget['fields']);
        }

        return view('vendor-estimates.form', compact('lead', 'estimate', 'budget'));
    }

    public function store(Request $request, VendorBuyerLead $lead)
    {
        $vendor = $this->vendor($request);
        abort_unless($lead->vendor_id === $vendor->id, 404);
        $currency = $this->currency($vendor);
        // The scenario is snapshotted at save time, not read live when the PDF
        // renders: an estimate must keep printing the figures it was sent with
        // after the vendor edits the calculator again.
        $budget = $request->boolean('attach_budget') ? $this->budgetFill($vendor, $currency) : null;
        $estimate = VendorLeadEstimate::create(array_merge($this->validated($request), [
            'vendor_id' => $vendor->id, 'vendor_buyer_lead_id' => $lead->id,
            'number' => 'GASQ-EST-'.now()->format('Ymd').'-'.strtoupper(Str::random(10)),
            'currency' => $currency,
            'buyer' => $lead->only(['contact_name', 'company', 'email', 'phone', 'site_address', 'service_type', 'start_date']),
            'budget_scenario' => $budget['scenario'] ?? null,
            'budget_scenario_at' => $budget ? now() : null,
        ]));

        return redirect()->route('vendor-estimates.edit', $estimate)->with('status', 'Estimate saved.');
    }

    public function edit(Request $request, VendorLeadEstimate $estimate)
    {
        $this->authorizeEstimate($request, $estimate);
        $lead = $estimate->lead;
        $estimate->load('attachments');
        $deliveries = $estimate->deliveries()->paginate(10);
        $canEmailEstimate = app(VendorEstimateDeliveryService::class)->schedulingBaseUrl($request->user()) !== null;
        $reviews = VendorReviewAppointment::where('vendor_id', $request->user()->id)->where('vendor_lead_estimate_id', $estimate->id)->where('status', 'scheduled')->where('ends_at', '<=', now())->latest('ends_at')->get();
        $accessHistory = VendorPdfAccessEvent::where('vendor_lead_estimate_id', $estimate->id)->latest('id')->paginate(30, ['*'], 'access_page');
        // Offered, not applied: on an existing estimate the vendor asks for the
        // fill explicitly, so saved pricing is never overwritten by a page load.
        $budgetAvailable = app(BudgetSummaryService::class)->forUser($request->user()) !== null;

        return view('vendor-estimates.form', compact('lead', 'estimate', 'deliveries', 'canEmailEstimate', 'reviews', 'accessHistory', 'budgetAvailable'));
    }

    /**
     * Fill this estimate's pricing from the vendor's saved Know Before You Bid
     * scenario, and attach that scenario as the estimate's dashboard.
     */
    public function attachBudget(Request $request, VendorLeadEstimate $estimate)
    {
        $this->authorizeEstimate($request, $estimate);
        $budget = $this->budgetFill($request->user(), $estimate->currency);
        if (! $budget) {
            return back()->withErrors(['budget' => 'Save a scenario in the Know Before You Bid Calculator first. Nothing has been saved to your account yet.']);
        }
        $estimate->update(array_merge($budget['fields'], [
            'budget_scenario' => $budget['scenario'],
            'budget_scenario_at' => now(),
        ]));

        return back()->with('status', 'Pricing filled from your Know Before You Bid figures, and the dashboard attached. Review the figures, then save.');
    }

    /** Detach the dashboard. The estimate keeps the pricing already filled in. */
    public function detachBudget(Request $request, VendorLeadEstimate $estimate)
    {
        $this->authorizeEstimate($request, $estimate);
        $estimate->update(['budget_scenario' => null, 'budget_scenario_at' => null]);

        return back()->with('status', 'Dashboard removed from this estimate. The pricing is unchanged.');
    }

    /**
     * The vendor's saved Know Before You Bid scenario, as estimate fields plus
     * the scenario to snapshot. Null when they have not saved one yet.
     *
     * Calculator figures are stored in USD, so the hourly rate is converted into
     * the estimate's own currency before it reaches a currency-labelled field.
     *
     * @return array{fields: array<string, mixed>, scenario: array<string, mixed>, summary: array<string, mixed>}|null
     */
    private function budgetFill($vendor, string $currency): ?array
    {
        $summary = app(BudgetSummaryService::class)->forUser($vendor);
        if (! $summary) {
            return null;
        }

        return [
            'fields' => [
                'hourly_rate' => round($summary['vendorHourly'] * Currency::rate($currency), 2),
                'weekly_hours' => round($summary['totalWeeklyHours'], 2),
                'weeks_per_year' => max(1, min(52, (int) round($summary['weeksInYear']))),
            ],
            'scenario' => $summary['budgetScenario'],
            'summary' => $summary,
        ];
    }

    public function send(Request $request, VendorLeadEstimate $estimate, VendorEstimateDeliveryService $deliveryService)
    {
        $this->authorizeEstimate($request, $estimate);
        $data = $request->validate([
            'pdf_password' => ['required', 'string', 'min:8', 'max:32', 'regex:/^[\x20-\x7E]+$/'],
            'request_key' => ['required', 'uuid'],
        ]);
        $delivery = $deliveryService->prepare($estimate, $data['pdf_password'], $data['request_key']);

        return redirect()->route('vendor-estimates.edit', $estimate)->with('status', 'Email delivery: '.VendorEstimateDelivery::STATUSES[$delivery->fresh()->status].'. Review delivery history below.');
    }

    /**
     * Run a stuck delivery's send job in this request instead of on the queue.
     *
     * A delivery is created as `queued` and its job dispatched immediately, so
     * one still queued minutes later means no queue worker claimed it. Rather
     * than leave the buyer without the estimate, let the vendor push it through.
     * The job claims the row with a conditional update, so a worker that wakes
     * up mid-flight cannot send a second copy.
     */
    public function flush(Request $request, VendorLeadEstimate $estimate, VendorEstimateDelivery $delivery)
    {
        $this->authorizeEstimate($request, $estimate);
        abort_unless($delivery->vendor_lead_estimate_id === $estimate->id, 404);
        abort_unless($delivery->status === 'queued', 422, 'Only a delivery still waiting on the queue can be sent from here.');

        SendVendorEstimateDelivery::dispatchSync($delivery->id);

        return redirect()->route('vendor-estimates.edit', $estimate)
            ->with('status', 'Email delivery: '.VendorEstimateDelivery::STATUSES[$delivery->fresh()->status].'. Review delivery history below.');
    }

    public function update(Request $request, VendorLeadEstimate $estimate)
    {
        $this->authorizeEstimate($request, $estimate);
        $estimate->update($this->validated($request));

        return back()->with('status', 'Estimate updated.');
    }

    public function download(Request $request, VendorLeadEstimate $estimate, ReportService $reports)
    {
        $this->authorizeEstimate($request, $estimate);
        abort_unless($estimate->status === 'ready', 422, 'Mark this estimate ready before downloading its PDF.');
        abort_if($estimate->valid_until->isBefore(today()), 422, 'Update the validity date before downloading this estimate.');
        $data = $request->validate(['pdf_password' => ['required', 'string', 'min:8', 'max:32', 'regex:/^[\x20-\x7E]+$/']]);
        $pdf = $reports->calculatorPdf('vendor-lead-estimate', ['estimate' => $estimate->load('vendor'), 'user' => $request->user()], $data['pdf_password']);

        // Render ONCE and send those exact bytes. $pdf->download() would render a
        // second time, and dompdf rewrites the permission mask (/P) on every render
        // after the first while leaving the password verifier (/U) as it was. The
        // file then fails its own password check and no password opens it. The
        // checksum we log must also be the checksum of the file actually sent.
        $bytes = $pdf->output();
        VendorPdfAccessEvent::create(['vendor_lead_estimate_id' => $estimate->id, 'actor_id' => $request->user()->id, 'event' => 'vendor_pdf_downloaded', 'pdf_sha256' => hash('sha256', $bytes), 'ip_address' => $request->ip()]);

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $estimate->number.'.pdf'),
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function vendor(Request $request)
    {
        abort_unless($request->user()->isVendor(), 403);

        return $request->user();
    }

    private function authorizeEstimate(Request $request, VendorLeadEstimate $estimate): void
    {
        abort_unless($estimate->vendor_id === $this->vendor($request)->id, 404);
    }

    private function currency($vendor): string
    {
        return array_key_exists($vendor->currency ?? '', config('currency.profiles', [])) ? $vendor->currency : 'USD';
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'], 'scope' => ['required', 'string', 'max:10000'],
            'hourly_rate' => ['required', 'numeric', 'gt:0', 'max:100000', 'decimal:0,2'],
            'weekly_hours' => ['required', 'numeric', 'gt:0', 'max:100000', 'decimal:0,2'],
            'weeks_per_year' => ['required', 'integer', 'min:1', 'max:52'],
            'one_time_cost' => ['required', 'numeric', 'min:0', 'max:100000000', 'decimal:0,2'],
            // Headcount, not money: whole officers only, and optional so an
            // estimate written before the field existed still validates.
            'staff_required' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'valid_until' => array_filter(['required', 'date_format:Y-m-d', $request->input('status') === 'ready' ? 'after_or_equal:today' : null]),
            'terms' => ['nullable', 'string', 'max:10000'], 'status' => ['required', Rule::in(array_keys(VendorLeadEstimate::STATUSES))],
        ]);
    }
}
