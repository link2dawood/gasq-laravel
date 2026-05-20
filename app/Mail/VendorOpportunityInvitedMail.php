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
        public string $vendorName = 'Vendor',
    ) {}

    public function envelope(): Envelope
    {
        $opportunity = $this->invitation->opportunity;
        $job = $opportunity?->jobPosting;
        $buyer = $job?->user;

        // Subject per spec: "ALERT! New Security Project in [City], [State] – Contract Value: $X"
        $contractValue = (float) ($opportunity?->estimated_annual_contract_value ?? 0);
        $valueStr = $contractValue > 0 ? '$' . number_format($contractValue, 2) : 'TBD';

        $city = $buyer?->city ?? '';
        $state = $buyer?->state ?? '';
        if (($city === '' || $state === '') && $job?->location
            && preg_match('/,\s*([^,]+),\s*([A-Z]{2})/', $job->location, $m)) {
            $city = $city ?: trim($m[1]);
            $state = $state ?: trim($m[2]);
        }
        $location = trim($city . ($state ? ', ' . $state : ''));
        if ($location === '') {
            $location = LeadFormatting::locationShort($job?->location);
        }

        return new Envelope(
            subject: "ALERT! New Security Opportunity Project in {$location} – Contract Value: {$valueStr}",
        );
    }

    public function content(): Content
    {
        $opportunity = $this->invitation->opportunity;
        $job = $opportunity?->jobPosting;
        $buyer = $job?->user;
        $questionnaire = is_array($job?->questionnaire_data) ? $job->questionnaire_data : [];

        // Pass the model so Laravel uses the route key name (invite_key UUID),
        // matching what VendorOpportunityNotification::invitationUrl() does.
        $url = URL::temporarySignedRoute(
            'vendor-opportunities.show',
            now()->addDays(14),
            ['invitation' => $this->invitation]
        );

        return new Content(
            view: 'emails.vendor-opportunity-invited',
            with: [
                'vendorName' => $this->vendorName,
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
