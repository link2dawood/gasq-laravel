<?php

namespace App\Services;

use App\Models\BillRateBreakdown;
use App\Models\User;
use App\Models\VendorOperatingVolume;

/**
 * Verified Shared Resource Rate(tm) — eligibility and approval rules.
 *
 * The rule this class exists to enforce (spec RULE 7/8):
 *
 *     1,000 verified active weekly billable hours earns a vendor the right to APPLY
 *     for Shared Resource pricing. It does not approve the rate.
 *
 * Approval additionally requires a complete line-item breakdown that reconciles to the
 * proposed rate, validated shared allocations, vendor certification, and GASQ approval
 * (spec §33, requirements 1–8).
 *
 * Shared Resource is an operating model, not a discount (RULE 17).
 */
class SharedResourceService
{
    // Vendor-facing statuses (spec §42).
    public const STATUS_NOT_VERIFIED = 'not_verified';
    public const STATUS_VERIFICATION_PENDING = 'verification_pending';
    public const STATUS_NOT_ELIGIBLE = 'not_eligible';
    public const STATUS_ELIGIBLE_FOR_REVIEW = 'eligible_for_review';
    public const STATUS_FINANCIAL_REVIEW_PENDING = 'financial_review_pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';

    public const STATUS_LABELS = [
        self::STATUS_NOT_VERIFIED => 'Not verified',
        self::STATUS_VERIFICATION_PENDING => 'Verification pending',
        self::STATUS_NOT_ELIGIBLE => 'Not eligible',
        self::STATUS_ELIGIBLE_FOR_REVIEW => 'Eligible for Shared Resource review',
        self::STATUS_FINANCIAL_REVIEW_PENDING => 'Financial review pending',
        self::STATUS_APPROVED => 'Verified Shared Resource approved',
        self::STATUS_REJECTED => 'Rejected',
        self::STATUS_EXPIRED => 'Expired — reverification required',
    ];

    /** The vendor's most recent operating-volume submission, if any. */
    public function latestVolume(User $vendor): ?VendorOperatingVolume
    {
        return VendorOperatingVolume::query()
            ->where('vendor_id', $vendor->id)
            ->latest('id')
            ->first();
    }

    /**
     * Has the vendor cleared the 1,000-hour gate?
     *
     * This answers eligibility to be REVIEWED — never approval of a rate.
     */
    public function isEligibleForReview(User $vendor): bool
    {
        return $this->latestVolume($vendor)?->isEligibleForReview() ?? false;
    }

    /**
     * The vendor's overall Shared Resource standing (spec §42).
     *
     * Derived rather than stored so it can never drift out of step with the underlying
     * volume and breakdown records.
     */
    public function statusFor(User $vendor, ?BillRateBreakdown $breakdown = null): string
    {
        $volume = $this->latestVolume($vendor);

        if (! $volume) {
            return self::STATUS_NOT_VERIFIED;
        }

        if ($volume->isExpired() || $volume->status === VendorOperatingVolume::STATUS_EXPIRED) {
            return self::STATUS_EXPIRED;
        }

        if ($volume->status === VendorOperatingVolume::STATUS_REJECTED) {
            return self::STATUS_REJECTED;
        }

        if ($volume->status === VendorOperatingVolume::STATUS_PENDING) {
            return self::STATUS_VERIFICATION_PENDING;
        }

        if ($volume->status === VendorOperatingVolume::STATUS_NOT_VERIFIED) {
            return self::STATUS_NOT_VERIFIED;
        }

        // Verified, but below the threshold.
        if (! $volume->meetsHoursThreshold()) {
            return self::STATUS_NOT_ELIGIBLE;
        }

        if (! $volume->isEligibleForReview()) {
            return self::STATUS_NOT_ELIGIBLE;
        }

        // Past the hours gate. Standing now depends on the financial review of the
        // breakdown — the hours alone approve nothing.
        $breakdown ??= $this->latestSharedResourceBreakdown($vendor);

        if (! $breakdown) {
            return self::STATUS_ELIGIBLE_FOR_REVIEW;
        }

        return match ($breakdown->status) {
            BillRateBreakdown::STATUS_APPROVED => self::STATUS_APPROVED,
            BillRateBreakdown::STATUS_REJECTED => self::STATUS_REJECTED,
            BillRateBreakdown::STATUS_EXPIRED => self::STATUS_EXPIRED,
            BillRateBreakdown::STATUS_SUBMITTED,
            BillRateBreakdown::STATUS_FINANCIAL_REVIEW_PENDING => self::STATUS_FINANCIAL_REVIEW_PENDING,
            default => self::STATUS_ELIGIBLE_FOR_REVIEW,
        };
    }

    public function latestSharedResourceBreakdown(User $vendor, ?int $jobPostingId = null): ?BillRateBreakdown
    {
        return BillRateBreakdown::query()
            ->where('vendor_id', $vendor->id)
            ->where('pricing_model', BillRateBreakdown::MODEL_SHARED_RESOURCE)
            ->when($jobPostingId !== null, fn ($q) => $q->where('job_posting_id', $jobPostingId))
            ->latest('id')
            ->first();
    }

    /**
     * Every reason the vendor may not submit Shared Resource pricing.
     *
     * Returns an empty array when all eight requirements of spec §33 are satisfied.
     * Returning all reasons rather than the first keeps the vendor from fixing one
     * problem at a time across repeated submissions.
     *
     * @return list<string>
     */
    public function blockingReasons(User $vendor, ?BillRateBreakdown $breakdown): array
    {
        $reasons = [];
        $volume = $this->latestVolume($vendor);

        // Requirements 1 & 2 — verified operating scale.
        if (! $volume) {
            $reasons[] = 'Operating volume has not been submitted for verification.';
        } elseif ($volume->isExpired()) {
            $reasons[] = 'Operating-volume verification has expired and must be renewed.';
        } elseif (! $volume->meetsHoursThreshold()) {
            $reasons[] = sprintf(
                'Verified weekly billable hours (%s) are below the %s-hour minimum.',
                rtrim(rtrim(number_format((float) $volume->weekly_billable_hours, 2), '0'), '.'),
                number_format(VendorOperatingVolume::MINIMUM_WEEKLY_HOURS)
            );
        } elseif (! $volume->isEligibleForReview()) {
            $reasons[] = 'Operating volume has not been verified by GASQ.';
        }

        // Requirement 3 — a complete breakdown must exist.
        if (! $breakdown) {
            $reasons[] = 'A complete line-item bill-rate breakdown is required for Shared Resource pricing.';

            return $reasons;
        }

        if ($breakdown->lineItems->isEmpty()) {
            $reasons[] = 'The bill-rate breakdown contains no line items.';
        }

        // Requirement 4 — it must reconcile (RULE 12).
        if (! $breakdown->reconcilesWithProposedRate()) {
            $reasons[] = sprintf(
                'Bill-rate breakdown does not reconcile: line items total $%s but the proposed rate is $%s.',
                number_format($breakdown->lineItemsSum(), 2),
                number_format((float) $breakdown->proposed_bill_rate, 2)
            );
        }

        // Requirement 5 — shared allocations must be supported (RULE 11).
        foreach ($breakdown->unsupportedSharedLines() as $line) {
            $reasons[] = sprintf(
                'Shared line "%s" must state its allocation method and the number of accounts it is spread across.',
                $line->label
            );
        }

        // Requirement 7 — vendor certification (RULE 14).
        if (! $breakdown->certified) {
            $reasons[] = 'The vendor bill-rate certification has not been completed.';
        }

        // Requirements 6 & 8 — GASQ financial review and approval (RULE 15).
        if ($breakdown->status !== BillRateBreakdown::STATUS_APPROVED) {
            $reasons[] = 'GASQ has not approved this Shared Resource bill-rate structure.';
        }

        return $reasons;
    }

    /**
     * May this vendor submit sealed Shared Resource pricing?
     *
     * All eight requirements of spec §33 must hold. RULE 16: expired, pending,
     * unverified or ineligible vendors cannot submit.
     */
    public function canSubmitSharedResourcePricing(User $vendor, ?BillRateBreakdown $breakdown): bool
    {
        return $this->blockingReasons($vendor, $breakdown) === [];
    }

    /**
     * Recompute and persist a breakdown's reconciliation state.
     *
     * Called before submission so `reconciles` and `line_items_total` always reflect the
     * line items actually stored, rather than whatever the client posted.
     */
    public function refreshReconciliation(BillRateBreakdown $breakdown): BillRateBreakdown
    {
        $breakdown->load('lineItems');

        $breakdown->forceFill([
            'line_items_total' => round($breakdown->lineItemsSum(), 2),
            'reconciles' => $breakdown->reconcilesWithProposedRate(),
        ])->save();

        return $breakdown;
    }
}
