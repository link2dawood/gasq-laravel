<?php

namespace App\Http\Controllers;

use App\Models\VendorBuyerLead;
use App\Models\VendorEstimateDelivery;
use App\Models\VendorLeadEstimate;
use App\Models\VendorPdfAccessEvent;
use App\Models\VendorReviewAppointment;
use App\Services\ReportService;
use App\Services\VendorEstimateDeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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
        $estimate = new VendorLeadEstimate([
            'title' => 'Security services estimate', 'scope' => $lead->scope, 'weekly_hours' => $lead->weekly_hours,
            'weeks_per_year' => 52, 'one_time_cost' => 0, 'valid_until' => now()->addDays(30), 'status' => 'draft',
            'currency' => $this->currency($vendor),
        ]);

        return view('vendor-estimates.form', compact('lead', 'estimate'));
    }

    public function store(Request $request, VendorBuyerLead $lead)
    {
        $vendor = $this->vendor($request);
        abort_unless($lead->vendor_id === $vendor->id, 404);
        $estimate = VendorLeadEstimate::create(array_merge($this->validated($request), [
            'vendor_id' => $vendor->id, 'vendor_buyer_lead_id' => $lead->id,
            'number' => 'GASQ-EST-'.now()->format('Ymd').'-'.strtoupper(Str::random(10)),
            'currency' => $this->currency($vendor),
            'buyer' => $lead->only(['contact_name', 'company', 'email', 'phone', 'site_address', 'service_type', 'start_date']),
        ]));

        return redirect()->route('vendor-estimates.edit', $estimate)->with('status', 'Estimate saved.');
    }

    public function edit(Request $request, VendorLeadEstimate $estimate)
    {
        $this->authorizeEstimate($request, $estimate);
        $lead = $estimate->lead;
        $deliveries = $estimate->deliveries()->paginate(10);
        $canEmailEstimate = app(VendorEstimateDeliveryService::class)->schedulingBaseUrl($request->user()) !== null;
        $reviews = VendorReviewAppointment::where('vendor_id', $request->user()->id)->where('vendor_lead_estimate_id', $estimate->id)->where('status', 'scheduled')->where('ends_at', '<=', now())->latest('ends_at')->get();
        $accessHistory = VendorPdfAccessEvent::where('vendor_lead_estimate_id', $estimate->id)->latest('id')->paginate(30, ['*'], 'access_page');

        return view('vendor-estimates.form', compact('lead', 'estimate', 'deliveries', 'canEmailEstimate', 'reviews', 'accessHistory'));
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

        $bytes = $pdf->output();
        VendorPdfAccessEvent::create(['vendor_lead_estimate_id' => $estimate->id, 'actor_id' => $request->user()->id, 'event' => 'vendor_pdf_downloaded', 'pdf_sha256' => hash('sha256', $bytes), 'ip_address' => $request->ip()]);

        return $pdf->download($estimate->number.'.pdf')->header('Cache-Control', 'private, no-store');
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
            'valid_until' => array_filter(['required', 'date_format:Y-m-d', $request->input('status') === 'ready' ? 'after_or_equal:today' : null]),
            'terms' => ['nullable', 'string', 'max:10000'], 'status' => ['required', Rule::in(array_keys(VendorLeadEstimate::STATUSES))],
        ]);
    }
}
