<?php

namespace App\Services;

use App\Mail\EstimateFollowUpMail;
use App\Models\EstimateFollowUp;
use App\Models\User;
use App\Support\Funnel;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EstimateFollowUpService
{
    /** Send once per distinct estimate; a changed result earns a new conversion bridge. */
    public function sendFor(?User $user, array $scenario, array $result): ?EstimateFollowUp
    {
        $meta = (array) data_get($scenario, 'meta', []);
        $email = trim((string) ($meta['requesterEmail'] ?? $user?->email ?? ''));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) return null;

        $hash = hash('sha256', $email . '|' . json_encode([$scenario, $result]));
        $followUp = EstimateFollowUp::firstOrCreate(['estimate_hash' => $hash], [
            'user_id' => $user?->id, 'email' => $email, 'tracking_token' => Str::random(48),
            'scenario' => $scenario, 'result' => $result,
        ]);
        if ($followUp->email_sent_at) return $followUp;

        Mail::to($email)->send(new EstimateFollowUpMail($followUp));
        $followUp->update(['email_sent_at' => now()]);
        Funnel::record(Funnel::ESTIMATE_FOLLOW_UP_SENT, ['estimate_id' => $followUp->id], $user?->id);
        return $followUp;
    }
}
