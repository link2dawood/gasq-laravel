<?php

namespace App\Mail;

use App\Models\VendorOpportunity;
use App\Support\LeadFormatting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminNewLeadMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public VendorOpportunity $opportunity,
    ) {}

    public function envelope(): Envelope
    {
        $tier = strtoupper((string) ($this->opportunity->lead_tier ?? '?'));
        $value = LeadFormatting::moneyShort($this->opportunity->estimated_annual_contract_value ?? 0);
        $location = LeadFormatting::locationShort($this->opportunity->jobPosting?->location);

        return new Envelope(
            subject: "[GASQ Admin] New Tier {$tier} Lead – {$value} – {$location}",
        );
    }

    public function content(): Content
    {
        $job = $this->opportunity->jobPosting;
        $buyer = $job?->user;

        return new Content(
            view: 'emails.admin-new-lead',
            with: [
                'opportunity' => $this->opportunity,
                'job' => $job,
                'buyer' => $buyer,
                'tier' => strtoupper((string) ($this->opportunity->lead_tier ?? '?')),
                'tierLabel' => $this->tierLabel($this->opportunity->lead_tier ?? ''),
                'contractValueFull' => LeadFormatting::moneyFull($this->opportunity->estimated_annual_contract_value ?? 0),
                'contractValueShort' => LeadFormatting::moneyShort($this->opportunity->estimated_annual_contract_value ?? 0),
                'adminUrl' => route('admin.vendor-opportunities.show', $this->opportunity),
            ],
        );
    }

    private function tierLabel(string $tier): string
    {
        return match (strtolower($tier)) {
            'a' => 'A — Auto-released to 5 vendors',
            'b' => 'B — Pending admin approval (then 3 vendors)',
            'c' => 'C — Held: buyer must update questionnaire',
            default => 'Unknown',
        };
    }
}
