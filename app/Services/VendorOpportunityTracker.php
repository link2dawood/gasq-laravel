<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use App\Models\User;
use App\Models\VendorOpportunityInvitation;
use Illuminate\Http\Request;

class VendorOpportunityTracker
{
    public function track(string $eventType, ?VendorOpportunityInvitation $invitation = null, ?User $user = null, array $data = [], ?Request $request = null): AnalyticsEvent
    {
        $payload = array_filter([
            'invitation_id' => $invitation?->id,
            'opportunity_id' => $invitation?->vendor_opportunity_id,
            'job_id' => $invitation?->opportunity?->job_posting_id,
        ] + $data, static fn (mixed $value): bool => $value !== null);

        return AnalyticsEvent::query()->create([
            'event_type' => $eventType,
            'user_id' => $user?->id,
            'event_data' => $payload,
            'session_id' => $request?->session()?->getId(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
