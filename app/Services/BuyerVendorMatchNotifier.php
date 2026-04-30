<?php

namespace App\Services;

use App\Models\User;
use App\Models\VendorOpportunity;
use App\Notifications\BuyerVendorMatchNotification;
use Throwable;

class BuyerVendorMatchNotifier
{
    public function __construct(
        private readonly PhoneOtpService $phoneOtpService,
        private readonly TwilioSmsService $smsService,
    ) {}

    public function notifyOpportunityLive(VendorOpportunity $opportunity): void
    {
        $this->send($opportunity, 'live', 0);
    }

    public function notifyAcceptedProgress(VendorOpportunity $opportunity): void
    {
        $acceptedCount = (int) $opportunity->acceptedInvitations()->count();

        $this->send($opportunity, 'accepted_progress', $acceptedCount);
    }

    private function send(VendorOpportunity $opportunity, string $type, int $acceptedCount): void
    {
        $opportunity->loadMissing('jobPosting.user');
        $buyer = $opportunity->jobPosting?->user;

        if (! $buyer instanceof User) {
            return;
        }

        $notification = new BuyerVendorMatchNotification($opportunity, $type, $acceptedCount);
        $buyer->notify($notification);

        $normalizedPhone = $this->phoneOtpService->normalizePhoneToE164((string) $buyer->phone);
        if (! is_string($normalizedPhone) || $normalizedPhone === '') {
            return;
        }

        try {
            $this->smsService->send($normalizedPhone, $notification->smsBody());
        } catch (Throwable $e) {
            report($e);
        }
    }
}
