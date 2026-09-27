<?php

namespace App\Services\VendorAccess;

use App\Models\User;
use App\Models\VendorOpportunityInvitation;

/**
 * Decides whether a signed-in vendor may open an invitation.
 *
 * Four separate questions, answered in order, because each one fails for a
 * different reason and the vendor deserves to be told which:
 *   1. Is this invitation still usable at all (expired, revoked, used up)?
 *   2. Does it belong to this vendor?
 *   3. Is the account a vendor account?
 *   4. Is the vendor verified and qualified enough to see a buyer opportunity?
 *
 * An administrator passes 2 to 4 so support can look at what a vendor sees.
 */
class VendorAccessGate
{
    /**
     * @return array{ok: bool, code?: string, message?: string}
     *                                                          code is one of: revoked, expired, exhausted, not_yours,
     *                                                          not_a_vendor, not_verified.
     */
    public function check(VendorOpportunityInvitation $invitation, ?User $user): array
    {
        if ($invitation->revoked_at !== null) {
            return $this->deny('revoked', 'This invitation was withdrawn by GASQ. Contact us if you think that is a mistake.');
        }

        if ($invitation->expires_at !== null && $invitation->expires_at->isPast()) {
            return $this->deny('expired', 'This invitation has expired. Ask GASQ to send a new one.');
        }

        if ($invitation->max_uses !== null && (int) $invitation->use_count >= (int) $invitation->max_uses) {
            return $this->deny('exhausted', 'This invitation has already been used the maximum number of times.');
        }

        if (! $user) {
            return $this->deny('not_yours', 'Sign in to open this opportunity.');
        }

        if ($user->isAdmin()) {
            return ['ok' => true];
        }

        if ((int) $user->id !== (int) $invitation->vendor_id) {
            // Deliberately vague: do not confirm that the invitation exists.
            return $this->deny('not_yours', 'This invitation was issued to a different vendor account.');
        }

        if (! $user->isVendor()) {
            return $this->deny('not_a_vendor', 'Only vendor accounts can open an opportunity.');
        }

        if (! $this->verified($user)) {
            return $this->deny(
                'not_verified',
                'Your vendor profile is not verified yet. Complete verification with GASQ before opening an opportunity.',
            );
        }

        return ['ok' => true];
    }

    /**
     * Verified and qualified, in the sense the buyer is promised: GASQ has
     * checked the company's credentials and marked the profile verified.
     */
    public function verified(User $user): bool
    {
        return (bool) ($user->vendorProfile?->is_verified);
    }

    /** @return array{ok: bool, code: string, message: string} */
    private function deny(string $code, string $message): array
    {
        return ['ok' => false, 'code' => $code, 'message' => $message];
    }
}
