<?php

namespace App\Support;

use App\Models\AnalyticsEvent;
use Illuminate\Support\Facades\Request as RequestFacade;

/**
 * Canonical funnel vocabulary.
 *
 * AnalyticsLogger already records every route hit, but a route hit is not a
 * business event: jobs.store bouncing on validation is not a completed job post,
 * and discovery-call.store throwing is not a booked meeting. These events are
 * therefore emitted explicitly, on the success path only, so the conversion
 * rates in the admin dashboard mean what they say.
 *
 * Recording is always best-effort — analytics must never break a user's request.
 */
class Funnel
{
    // Entry / intent
    public const ESTIMATE_STARTED = 'estimate_started';
    public const QUOTE_VALIDATION_STARTED = 'quote_validation_started';
    public const BUYER_PRICING_VIEWED = 'buyer_pricing_viewed';
    public const VENDOR_PRICING_VIEWED = 'vendor_pricing_viewed';

    // Account
    public const REGISTRATION_STARTED = 'registration_started';
    public const REGISTRATION_COMPLETED = 'registration_completed';

    // Conversion
    public const REPORT_PURCHASED = 'report_purchased';
    public const ESTIMATE_COMPLETED = 'estimate_completed';
    public const ESTIMATE_FOLLOW_UP_SENT = 'estimate_follow_up_sent';
    public const ESTIMATE_FOLLOW_UP_OPENED = 'estimate_follow_up_opened';
    public const ESTIMATE_FOLLOW_UP_CTA_CLICKED = 'estimate_follow_up_cta_clicked';
    public const BUYER_COMMITMENT_FEE_INITIATED = 'buyer_commitment_fee_initiated';
    public const BUYER_COMMITMENT_FEE_COMPLETED = 'buyer_commitment_fee_completed';
    public const JOB_POST_STARTED = 'job_post_started';
    public const JOB_POST_COMPLETED = 'job_post_completed';
    public const VENDOR_MATCH_STARTED = 'vendor_match_started';
    public const MEETING_BOOKED = 'meeting_booked';

    /**
     * The buyer journey in order. Stage-to-stage conversion is computed from
     * this list, so the order is meaningful — do not reorder casually.
     *
     * @var list<array{key: string, label: string}>
     */
    public const BUYER_JOURNEY = [
        ['key' => self::ESTIMATE_STARTED, 'label' => 'Estimate started'],
        ['key' => self::ESTIMATE_COMPLETED, 'label' => 'Estimate completed'],
        ['key' => self::REGISTRATION_STARTED, 'label' => 'Registration started'],
        ['key' => self::REGISTRATION_COMPLETED, 'label' => 'Registration completed'],
        ['key' => self::JOB_POST_STARTED, 'label' => 'Job post started'],
        ['key' => self::JOB_POST_COMPLETED, 'label' => 'Job post completed'],
        ['key' => self::VENDOR_MATCH_STARTED, 'label' => 'Vendor match started'],
        ['key' => self::MEETING_BOOKED, 'label' => 'Meeting booked'],
    ];

    /**
     * Side entries — real intent signals, but not linear stages of the journey
     * above, so they are reported as standalone counters rather than folded into
     * a conversion rate that would misrepresent them.
     *
     * @var list<array{key: string, label: string}>
     */
    public const SIDE_ENTRIES = [
        ['key' => self::QUOTE_VALIDATION_STARTED, 'label' => 'Quote validation started'],
        ['key' => self::REPORT_PURCHASED, 'label' => 'Report purchased'],
        ['key' => self::ESTIMATE_FOLLOW_UP_SENT, 'label' => 'Estimate follow-up sent'],
        ['key' => self::BUYER_PRICING_VIEWED, 'label' => 'Buyer pricing viewed'],
        ['key' => self::VENDOR_PRICING_VIEWED, 'label' => 'Vendor pricing viewed'],
    ];

    /**
     * Record a funnel event.
     *
     * @param  array<string, mixed>  $data  Non-identifying context only — never
     *                                      request bodies, credentials or card details.
     */
    public static function record(string $event, array $data = [], ?int $userId = null): void
    {
        try {
            $request = RequestFacade::instance();

            AnalyticsEvent::create([
                'event_type' => $event,
                'user_id' => $userId ?? $request->user()?->id,
                'event_data' => $data + ['funnel' => true],
                'session_id' => $request->hasSession() ? $request->session()->getId() : null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (\Throwable) {
            // Never break the request because of analytics.
        }
    }
}
