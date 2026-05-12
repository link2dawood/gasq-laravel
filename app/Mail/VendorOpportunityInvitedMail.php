<?php

namespace App\Mail;

use App\Models\VendorOpportunityInvitation;
use App\Support\LeadFormatting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class VendorOpportunityInvitedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public VendorOpportunityInvitation $invitation,
    ) {}

    public function envelope(): Envelope
    {
        $opportunity = $this->invitation->opportunity;
        $job = $opportunity?->jobPosting;
        $buyer = $job?->user;

        $value = LeadFormatting::moneyShort($opportunity?->estimated_annual_contract_value ?? 0);
        $location = LeadFormatting::locationShort($job?->location);

        // Build a slash-joined verification tag from whichever of the three flags are set.
        // e.g. "Decision Maker/Budget/Phone # Verified" when all three pass.
        $parts = [];
        if ($opportunity?->decision_maker_verified) $parts[] = 'Decision Maker';
        if ($opportunity?->budget_confirmed) $parts[] = 'Budget';
        if ($buyer?->phone_verified) $parts[] = 'Phone #';
        $tag = $parts === [] ? 'Pending Verification' : implode('/', $parts) . ' Verified';

        return new Envelope(
            subject: "GASQ ALERT: {$value} Security Contract – {$location} ({$tag})",
        );
    }

    public function content(): Content
    {
        $opportunity = $this->invitation->opportunity;
        $job = $opportunity?->jobPosting;
        $buyer = $job?->user;
        $questionnaire = is_array($job?->questionnaire_data) ? $job->questionnaire_data : [];

        $url = URL::temporarySignedRoute(
            'vendor-opportunities.show',
            now()->addDays(14),
            ['invitation' => $this->invitation->id]
        );

        return new Content(
            view: 'emails.vendor-opportunity-invited',
            with: [
                'invitation' => $this->invitation,
                'opportunity' => $opportunity,
                'job' => $job,
                'buyer' => $buyer,
                'questionnaire' => $questionnaire,
                'url' => $url,
                'redactedName' => LeadFormatting::redactName($buyer?->name),
                'redactedEmail' => LeadFormatting::redactEmail($buyer?->email),
                'redactedPhone' => LeadFormatting::redactPhone($buyer?->phone),
                'phoneVerified' => (bool) ($buyer?->phone_verified ?? false),
                'decisionMakerVerified' => (bool) ($opportunity?->decision_maker_verified ?? false),
                'budgetConfirmed' => (bool) ($opportunity?->budget_confirmed ?? false),
                'scopeCompleted' => (bool) ($opportunity?->scope_completed ?? false),
                'moveForward' => (bool) ($opportunity?->move_forward_confirmed ?? false),
                'contractValueShort' => LeadFormatting::moneyShort($opportunity?->estimated_annual_contract_value ?? 0),
                'contractValueFull' => LeadFormatting::moneyFull($opportunity?->estimated_annual_contract_value ?? 0),
                'creditsToUnlock' => (int) ($this->invitation->credits_to_unlock ?? 0),
                'maxAccepts' => (int) ($opportunity?->max_accepts ?? 5),
                'currentAccepts' => (int) ($opportunity?->invitations()
                    ->whereIn('status', ['accepted', 'bid_submitted'])->count() ?? 0),
                'startTimeline' => (string) ($questionnaire['service_start_timeline'] ?? 'TBD'),
                'serviceType' => (string) ($job?->category ?? 'Security Services'),
            ],
        );
    }
}
