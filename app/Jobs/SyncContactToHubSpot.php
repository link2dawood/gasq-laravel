<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\HubSpotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Outbound contact sync to HubSpot (Layer 1). Queued so HubSpot latency or
 * downtime never blocks a signup, contact form, or Stripe webhook.
 *
 * Pass a userId for a registered user, or an email + leadProperties for a
 * guest lead (e.g. the contact form).
 */
class SyncContactToHubSpot implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    /**
     * @param  array<string, mixed>  $extraProperties  extra gasq_* custom properties (e.g. gasq_last_purchase_usd)
     * @param  array<string, mixed>  $leadProperties   name/company/phone for guest leads with no User
     */
    public function __construct(
        public ?int $userId,
        public ?string $email = null,
        public array $extraProperties = [],
        public array $leadProperties = [],
    ) {}

    public function handle(HubSpotService $hubspot): void
    {
        if (! $hubspot->enabled()) {
            return;
        }

        $user = $this->userId ? User::find($this->userId) : null;
        $email = $user?->email ?? $this->email;

        if (! $email) {
            return;
        }

        [$firstName, $lastName] = $this->splitName($user?->name ?? ($this->leadProperties['name'] ?? ''));

        $standard = [
            'firstname' => $firstName,
            'lastname' => $lastName,
            'company' => $user?->company ?? ($this->leadProperties['company'] ?? null),
            'phone' => $user?->phone ?? ($this->leadProperties['phone'] ?? null),
        ];

        $custom = array_merge([
            'gasq_user_id' => $user?->id,
            'gasq_role' => $user?->user_type,
        ], $this->extraProperties);

        $contactId = $hubspot->upsertContactByEmail($email, $standard, $custom);

        if ($contactId && $user && $user->hubspot_contact_id !== $contactId) {
            // saveQuietly: don't fire model events (keeps future inbound sync loop-safe).
            $user->forceFill(['hubspot_contact_id' => $contactId])->saveQuietly();
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $name): array
    {
        $name = trim($name);
        if ($name === '') {
            return ['', ''];
        }

        $parts = preg_split('/\s+/', $name, 2) ?: [];

        return [$parts[0] ?? '', $parts[1] ?? ''];
    }
}
