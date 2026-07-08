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
 * Inbound sync (Layer 2): apply a HubSpot contact's current values onto the
 * matching local user. Runs on the queue so the webhook can 200 immediately.
 *
 * The site owns email, role (user_type), and credit balance — those are never
 * written from HubSpot. Only name / phone / company flow inbound.
 */
class ApplyHubSpotContactChange implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    /** HubSpot property => local user column. */
    private const FIELD_MAP = [
        'phone' => 'phone',
        'company' => 'company',
    ];

    public function __construct(public string $contactId) {}

    public function handle(HubSpotService $hubspot): void
    {
        if (! $hubspot->enabled()) {
            return;
        }

        $props = $hubspot->getContact($this->contactId, ['email', 'firstname', 'lastname', 'phone', 'company']);
        if ($props === null) {
            return;
        }

        $user = User::where('hubspot_contact_id', $this->contactId)->first();

        if (! $user) {
            // Unlinked contact (e.g. a creation event): link an existing user by
            // email. We never create app users from arbitrary HubSpot contacts.
            $email = $props['email'] ?? null;
            if (! $email) {
                return;
            }
            $user = User::whereRaw('LOWER(email) = ?', [strtolower((string) $email)])->first();
            if (! $user) {
                return;
            }
            $user->hubspot_contact_id = $this->contactId;
        }

        // Rebuild the single name field from HubSpot's first + last.
        $fullName = trim(trim((string) ($props['firstname'] ?? '')) . ' ' . trim((string) ($props['lastname'] ?? '')));
        if ($fullName !== '' && $fullName !== (string) $user->name) {
            $user->name = $fullName;
        }

        foreach (self::FIELD_MAP as $hubspotKey => $column) {
            $value = $props[$hubspotKey] ?? null;
            if ($value !== null && $value !== '' && (string) $value !== (string) $user->{$column}) {
                $user->{$column} = $value;
            }
        }

        if ($user->isDirty()) {
            // saveQuietly: no model events, so this never re-triggers an outbound sync.
            $user->saveQuietly();
        }
    }
}
