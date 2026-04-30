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
        $this->send($opportunity, BuyerVendorMatchNotification::TYPE_LIVE, 0, true);
    }

    public function notifyAcceptedProgress(VendorOpportunity $opportunity): void
    {
        $acceptedCount = (int) $opportunity->acceptedInvitations()->count();

        $this->send($opportunity, BuyerVendorMatchNotification::TYPE_ACCEPTED_PROGRESS, $acceptedCount, true);
    }

    public function notifyPendingQualification(VendorOpportunity $opportunity): void
    {
        $this->send($opportunity, BuyerVendorMatchNotification::TYPE_PENDING_QUALIFICATION, 0, false);
    }

    private function send(VendorOpportunity $opportunity, string $type, int $acceptedCount, bool $sendSms): void
    {
        $opportunity->loadMissing('jobPosting.user');
        $buyer = $opportunity->jobPosting?->user;

        if (! $buyer instanceof User) {
            return;
        }

        $notification = new BuyerVendorMatchNotification($opportunity, $type, $acceptedCount);
        $buyer->notify($notification);

        if (! $sendSms) {
            return;
        }

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
