<?php

namespace App\Http\Controllers;

use App\Models\Bid;
use App\Models\User;
use App\Models\VendorOpportunityInvitation;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class VendorLeadsController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user?->isVendor(), 403);

        $view = $request->string('view')->toString();
        $leads = $this->leadItemsForVendor($user, $view);
        $selectedKey = $request->string('lead')->toString();
        $selectedLead = $leads->firstWhere('key', $selectedKey) ?? $leads->first();

        return view('vendor-leads.index', [
            'leadItems' => $leads,
            'selectedLead' => $selectedLead,
            'leadView' => $view,
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function leadItemsForVendor(User $user, string $view): Collection
    {
        $items = collect();

        if (Schema::hasTable('vendor_opportunity_invitations') && Schema::hasTable('vendor_opportunities')) {
            $invitations = VendorOpportunityInvitation::query()
                ->with(['opportunity.jobPosting.user', 'opportunity.invitations', 'bid'])
                ->where('vendor_id', $user->id)
                ->latest('sent_at')
                ->latest('created_at')
                ->get();

            foreach ($invitations as $invitation) {
                $item = $this->invitationLeadItem($invitation);
                if ($this->matchesViewFilter($item, $view)) {
                    $items->push($item);
                }
            }
        }

        $legacyBids = Bid::query()
            ->with(['jobPosting.user', 'jobPosting.bids'])
            ->where('user_id', $user->id)
            ->when(
                Schema::hasColumn('bids', 'vendor_opportunity_invitation_id'),
                fn ($query) => $query->whereNull('vendor_opportunity_invitation_id')
            )
            ->latest()
            ->get();

        foreach ($legacyBids as $bid) {
            $item = $this->legacyBidLeadItem($bid);
            if ($this->matchesViewFilter($item, $view)) {
                $items->push($item);
            }
        }

        return $items
            ->sortByDesc('sort_timestamp')
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function invitationLeadItem(VendorOpportunityInvitation $invitation): array
    {
        $opportunity = $invitation->opportunity;
        $job = $opportunity?->jobPosting;
        $buyer = $job?->user;
        $questionnaire = is_array($job?->questionnaire_data) ? $job->questionnaire_data : [];
        $respondedCount = $opportunity ? (int) $opportunity->respondedInvitations()->count() : 0;
        $acceptedCount = $opportunity ? (int) $opportunity->acceptedInvitations()->count() : 0;
        $serviceType = $this->serviceTypeSummary($job?->category, $questionnaire, $job?->title);
        $buyerName = $buyer?->name ?: 'Buyer';

        return [
            'key' => 'opportunity:' . $invitation->invite_key,
            'type' => 'opportunity',
            'title' => $serviceType,
            'subtitle' => $buyerName,
            'buyer_name' => $buyerName,
            'buyer_email' => $invitation->buyerDetailsUnlocked() ? ($buyer?->email ?: 'Not provided') : $this->maskEmail((string) ($buyer?->email ?? '')),
            'buyer_phone' => $invitation->buyerDetailsUnlocked() ? ($buyer?->phone ?: 'Not provided') : $this->maskPhone((string) ($buyer?->phone ?? '')),
            'buyer_contact_action' => $invitation->buyerDetailsUnlocked() ? ('Contact ' . $buyerName) : 'Review lead',
            'location' => $job?->location ?: 'Not provided',
            'summary' => 'Looking for ' . $serviceType,
            'additional_details' => $this->valueText(data_get($questionnaire, 'additional_notes_to_vendors') ?: data_get($questionnaire, 'primary_reason')),
            'credits' => (int) $invitation->credits_to_unlock,
            'age_badge' => $this->shortAge($invitation->sent_at ?? $invitation->created_at),
            'response_label' => sprintf('%d/%d professionals have responded', $respondedCount, (int) ($opportunity?->max_accepts ?? 5)),
            'response_count' => $respondedCount,
            'response_denominator' => (int) ($opportunity?->max_accepts ?? 5),
            'accepted_count' => $acceptedCount,
            'status' => $invitation->status,
            'status_label' => ucfirst(str_replace('_', ' ', $invitation->status)),
            'is_responded' => $invitation->hasResponded(),
            'detail_rows' => $this->projectDetails($questionnaire),
            'verification_rows' => [
                'Phone Number Verified' => (bool) data_get($questionnaire, 'phone_verified', $buyer?->phone_verified ?? false),
                'Decision Maker' => in_array(data_get($questionnaire, 'final_decision_maker'), ['yes', 'authorized_representative'], true),
                'Total Bid Offer Verified' => $this->moneyText($opportunity?->estimated_annual_contract_value),
                'Total Credits to Respond' => number_format((int) $invitation->credits_to_unlock),
            ],
            'details_intro' => $this->valueText(data_get($questionnaire, 'primary_reason')),
            'action_url' => route('vendor-opportunities.manage', $invitation),
            'sort_timestamp' => ($invitation->sent_at ?? $invitation->created_at)?->timestamp ?? 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function legacyBidLeadItem(Bid $bid): array
    {
        $job = $bid->jobPosting;
        $buyer = $job?->user;
        $questionnaire = is_array($job?->questionnaire_data) ? $job->questionnaire_data : [];
        $serviceType = $this->serviceTypeSummary($job?->category, $questionnaire, $job?->title);
        $acceptedCount = $job ? (int) $job->bids()->where('status', 'accepted')->count() : 0;
        $buyerName = $buyer?->name ?: 'Buyer';

        return [
            'key' => 'legacy:' . $bid->id,
            'type' => 'legacy',
            'title' => $serviceType,
            'subtitle' => $buyerName,
            'buyer_name' => $buyerName,
            'buyer_email' => $this->maskEmail((string) ($buyer?->email ?? '')),
            'buyer_phone' => $this->maskPhone((string) ($buyer?->phone ?? '')),
            'buyer_contact_action' => 'Open classic lead',
            'location' => $job?->location ?: 'Not provided',
            'summary' => 'Looking for ' . $serviceType,
            'additional_details' => $this->valueText(data_get($questionnaire, 'additional_notes_to_vendors') ?: data_get($questionnaire, 'primary_reason')),
            'credits' => (int) floor(((float) $bid->amount) / 100),
            'age_badge' => $this->shortAge($bid->created_at),
            'response_label' => sprintf('%d/%d professionals have responded', $acceptedCount, 5),
            'response_count' => $acceptedCount,
            'response_denominator' => 5,
            'accepted_count' => $acceptedCount,
            'status' => $bid->vendor_response_status ?? $bid->status,
            'status_label' => ucfirst(str_replace('_', ' ', (string) ($bid->vendor_response_status ?? $bid->status))),
            'is_responded' => method_exists($bid, 'hasVendorResponded') ? $bid->hasVendorResponded() : $bid->status !== 'pending',
            'detail_rows' => $this->projectDetails($questionnaire),
            'verification_rows' => [
                'Phone Number Verified' => (bool) data_get($questionnaire, 'phone_verified', $buyer?->phone_verified ?? false),
                'Decision Maker' => in_array(data_get($questionnaire, 'final_decision_maker'), ['yes', 'authorized_representative'], true),
                'Total Bid Offer Verified' => $this->moneyText($bid->amount),
                'Total Credits to Respond' => number_format((int) floor(((float) $bid->amount) / 100)),
            ],
            'details_intro' => $this->valueText(data_get($questionnaire, 'primary_reason')),
            'action_url' => route('open-bid-offer.index', ['bid' => $bid->id]),
            'sort_timestamp' => $bid->created_at?->timestamp ?? 0,
        ];
    }

    private function matchesViewFilter(array $item, string $view): bool
    {
        return match ($view) {
            'responses' => (bool) ($item['is_responded'] ?? false),
            default => true,
        };
    }

    private function shortAge(?Carbon $date): string
    {
        if (! $date instanceof Carbon) {
            return 'New';
        }

        $now = now();
        $diffInMinutes = max(0, (int) floor($date->diffInMinutes($now)));

        if ($diffInMinutes < 60) {
            return max(1, $diffInMinutes) . 'm ago';
        }

        $diffInHours = (int) floor($date->diffInHours($now));
        if ($diffInHours < 48) {
            return $diffInHours . 'h ago';
        }

        $diffInDays = (int) floor($date->diffInDays($now));

        return $diffInDays . 'd ago';
    }

    /**
     * @param  array<string, mixed>  $questionnaire
     * @return array<int, array{label: string, value: string}>
     */
    private function projectDetails(array $questionnaire): array
    {
        return [
            [
                'label' => 'What is this service for?',
                'value' => $this->serviceForText((string) data_get($questionnaire, 'request_type')),
            ],
            [
                'label' => 'Are you the person authorized to make a final buying commitment with the vendor or approve payment for the proposed services?',
                'value' => $this->authorizedDecisionMakerText((string) data_get($questionnaire, 'final_decision_maker')),
            ],
            [
                'label' => 'Are you price shopping?',
                'value' => $this->priceShoppingText($questionnaire),
            ],
            [
                'label' => 'How likely are you to make a hiring decision?',
                'value' => $this->hiringDecisionLikelihoodText($questionnaire),
            ],
            [
                'label' => 'What is your sense of urgency for hiring a security vendor to start this security project?',
                'value' => $this->hiringUrgencyText($questionnaire),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $questionnaire
     */
    private function serviceTypeSummary(?string $category, array $questionnaire, ?string $fallbackTitle): string
    {
        if ($category) {
            return $category;
        }

        $services = array_values(array_filter(array_map(
            static fn ($value) => is_scalar($value) ? (string) $value : null,
            (array) data_get($questionnaire, 'service_types', [])
        )));

        if ($services !== []) {
            return implode(', ', $services);
        }

        return $fallbackTitle ?: 'Security Service';
    }

    private function serviceForText(?string $requestType): string
    {
        return match ($requestType) {
            'new_service' => 'New Purchase of Services',
            'replace_current_provider' => 'Replacement of Current Services',
            'expand_existing_coverage' => 'Expanded Purchase of Services',
            'temporary_emergency_coverage' => 'Emergency or Temporary Services',
            null, '' => 'Not provided',
            default => ucwords(str_replace('_', ' ', $requestType)),
        };
    }

    private function authorizedDecisionMakerText(?string $value): string
    {
        return match ($value) {
            'yes', 'authorized_representative' => 'Yes',
            'no' => 'No',
            null, '' => 'Not provided',
            default => ucwords(str_replace('_', ' ', $value)),
        };
    }

    /**
     * @param  array<string, mixed>  $questionnaire
     */
    private function priceShoppingText(array $questionnaire): string
    {
        $moveForward = data_get($questionnaire, 'move_forward_if_accepted');
        $allowScopeAdjustment = data_get($questionnaire, 'allow_scope_adjustment');

        if ($moveForward === 'yes') {
            return "No, I'm ready to purchase at a fair & reasonable price";
        }

        if ($allowScopeAdjustment === 'maybe_after_review') {
            return "I'm still comparing pricing and scope options";
        }

        if ($allowScopeAdjustment === 'yes') {
            return 'Yes, I may still adjust the scope or pricing';
        }

        if ($allowScopeAdjustment === 'no') {
            return 'No, I am not price shopping';
        }

        return 'Not provided';
    }

    /**
     * @param  array<string, mixed>  $questionnaire
     */
    private function hiringDecisionLikelihoodText(array $questionnaire): string
    {
        return match (data_get($questionnaire, 'move_forward_if_accepted')) {
            'yes' => "I'm ready to hire right now",
            'need_internal_review' => 'Likely after internal review',
            'no' => 'Not ready to hire yet',
            default => 'Not provided',
        };
    }

    /**
     * @param  array<string, mixed>  $questionnaire
     */
    private function hiringUrgencyText(array $questionnaire): string
    {
        return match (data_get($questionnaire, 'service_start_timeline')) {
            'immediate' => 'Immediately',
            '15_days_or_less' => 'Within 15 days',
            '30_days_or_less' => 'Within 30 days',
            '30_60_days' => 'Within 30-45 days',
            'future_planning' => 'Future planning',
            default => 'Not provided',
        };
    }

    private function maskEmail(string $email): string
    {
        if ($email === '' || ! str_contains($email, '@')) {
            return 'Not provided';
        }

        [$name, $domain] = explode('@', $email, 2);
        $maskedName = substr($name, 0, 1) . str_repeat('*', max(4, strlen($name) - 1));
        $domainParts = explode('.', $domain);
        $tld = count($domainParts) > 1 ? array_pop($domainParts) : null;
        $label = implode('.', $domainParts);
        $maskedLabel = $label === '' ? '***' : substr($label, 0, 1) . str_repeat('*', max(4, strlen($label) - 1));

        return $maskedName . '@' . $maskedLabel . ($tld ? '.' . $tld : '');
    }

    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) >= 10) {
            return substr($digits, -10, 3) . '*******';
        }

        return 'Not provided';
    }

    private function moneyText(mixed $value): string
    {
        if (! is_numeric($value)) {
            return 'Not provided';
        }

        return '$' . number_format((float) $value, 2);
    }

    private function valueText(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'Not provided';
        }

        return (string) $value;
    }
}
