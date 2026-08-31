<?php

namespace App\Http\Controllers;

use App\Models\BillRateBreakdown;
use App\Models\BillRateLineItem;
use App\Models\VendorOperatingVolume;
use App\Services\SharedResourceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Vendor-facing Verified Shared Resource Rate(tm) workflow (review spec §10-13).
 *
 * Two gates, in order:
 *   1. Prove operating scale — 1,000 active weekly billable hours (§11)
 *   2. Submit a complete line-item bill-rate breakdown that reconciles (§13)
 *
 * Clearing gate 1 only earns the right to attempt gate 2. It never approves a rate.
 */
class SharedResourceController extends Controller
{
    public function __construct(
        private SharedResourceService $sharedResource,
    ) {}

    /** Status page: where the vendor stands and exactly what is still blocking them. */
    public function index(Request $request): View
    {
        $vendor = $request->user();
        $volume = $this->sharedResource->latestVolume($vendor);
        $breakdown = $this->sharedResource->latestSharedResourceBreakdown($vendor);

        return view('vendor.shared-resource.index', [
            'volume' => $volume,
            'breakdown' => $breakdown?->load('lineItems'),
            'status' => $this->sharedResource->statusFor($vendor, $breakdown),
            'statusLabels' => SharedResourceService::STATUS_LABELS,
            'eligibleForReview' => $this->sharedResource->isEligibleForReview($vendor),
            'blockingReasons' => $this->sharedResource->blockingReasons($vendor, $breakdown?->load('lineItems')),
            'minimumHours' => VendorOperatingVolume::MINIMUM_WEEKLY_HOURS,
        ]);
    }

    /**
     * Submit an operating-volume claim for verification.
     *
     * Recorded as PENDING, never as verified: a vendor cannot verify its own scale
     * (§11 — it must be a verified pricing privilege, not a self-selection).
     */
    public function storeVolume(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'weekly_billable_hours' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'account_count' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'evidence_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        VendorOperatingVolume::create([
            'vendor_id' => $request->user()->id,
            'weekly_billable_hours' => $data['weekly_billable_hours'],
            'account_count' => $data['account_count'] ?? null,
            'evidence_notes' => $data['evidence_notes'] ?? null,
            'status' => VendorOperatingVolume::STATUS_PENDING,
            'submitted_at' => now(),
        ]);

        return redirect()->route('shared-resource.index')->with(
            'success',
            'Operating volume submitted. GASQ will verify your weekly billable hours before Shared Resource review can begin.'
        );
    }

    /** Build or revise the line-item bill-rate breakdown. */
    public function editBreakdown(Request $request): View|RedirectResponse
    {
        $vendor = $request->user();

        if (! $this->sharedResource->isEligibleForReview($vendor)) {
            return redirect()->route('shared-resource.index')->with(
                'error',
                'Your operating volume must be verified at or above the minimum before you can submit a Shared Resource bill-rate breakdown.'
            );
        }

        $breakdown = $this->sharedResource->latestSharedResourceBreakdown($vendor);

        return view('vendor.shared-resource.breakdown', [
            'breakdown' => $breakdown?->load('lineItems'),
            'categories' => BillRateLineItem::CATEGORIES,
            'classifications' => BillRateLineItem::CLASSIFICATIONS,
        ]);
    }

    /**
     * Save the breakdown.
     *
     * Reconciliation is recomputed from the stored line items — never trusted from the
     * request — and submission is blocked unless it balances (§13, RULE 12).
     */
    public function storeBreakdown(Request $request): RedirectResponse
    {
        $vendor = $request->user();

        if (! $this->sharedResource->isEligibleForReview($vendor)) {
            return redirect()->route('shared-resource.index')->with(
                'error',
                'Your operating volume must be verified before a Shared Resource breakdown can be submitted.'
            );
        }

        $data = $request->validate([
            'proposed_bill_rate' => ['required', 'numeric', 'min:0.01', 'max:10000'],
            'certified' => ['accepted'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.category' => ['required', 'string', 'in:' . implode(',', array_keys(BillRateLineItem::CATEGORIES))],
            'lines.*.label' => ['required', 'string', 'max:255'],
            'lines.*.amount' => ['required', 'numeric', 'min:0', 'max:10000'],
            'lines.*.resource_classification' => ['required', 'string', 'in:' . implode(',', array_keys(BillRateLineItem::CLASSIFICATIONS))],
            'lines.*.allocation_method' => ['nullable', 'string', 'max:1000'],
            'lines.*.shared_across_accounts' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ], [
            'certified.accepted' => 'You must certify the accuracy of the bill-rate breakdown before submitting.',
        ]);

        $previous = $this->sharedResource->latestSharedResourceBreakdown($vendor);

        $breakdown = BillRateBreakdown::create([
            'vendor_id' => $vendor->id,
            'version' => ($previous?->version ?? 0) + 1,
            'pricing_model' => BillRateBreakdown::MODEL_SHARED_RESOURCE,
            'proposed_bill_rate' => $data['proposed_bill_rate'],
            'status' => BillRateBreakdown::STATUS_SUBMITTED,
            'certified' => true,
            'certified_at' => now(),
            'submitted_at' => now(),
        ]);

        foreach (array_values($data['lines']) as $i => $line) {
            BillRateLineItem::create([
                'bill_rate_breakdown_id' => $breakdown->id,
                'category' => $line['category'],
                'label' => $line['label'],
                'amount' => $line['amount'],
                'resource_classification' => $line['resource_classification'],
                'allocation_method' => $line['allocation_method'] ?? null,
                'shared_across_accounts' => $line['shared_across_accounts'] ?? null,
                'sort_order' => $i,
            ]);
        }

        $breakdown = $this->sharedResource->refreshReconciliation($breakdown);

        // Spec §39 — a breakdown that does not balance is blocked, not filed.
        if (! $breakdown->reconciles) {
            $breakdown->forceFill(['status' => BillRateBreakdown::STATUS_DRAFT])->save();

            return redirect()->route('shared-resource.breakdown.edit')->with(
                'error',
                sprintf(
                    'Bill-rate breakdown does not reconcile. Your line items total $%s but your proposed rate is $%s. Correct the breakdown before submitting.',
                    number_format($breakdown->lineItemsSum(), 2),
                    number_format((float) $breakdown->proposed_bill_rate, 2)
                )
            );
        }

        return redirect()->route('shared-resource.index')->with(
            'success',
            'Bill-rate breakdown submitted and reconciled. GASQ will now review your Shared Resource pricing structure.'
        );
    }
}
