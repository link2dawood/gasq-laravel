<?php

namespace App\Support;

use App\Models\VendorBuyerLead;
use Carbon\CarbonInterface;

/**
 * The GASQ Security Services Opportunity Qualification Form.
 *
 * One definition of the form, read by the public capture page, the controller that
 * validates it, the vendor's lead record and the submission email. Question numbers
 * and wording match the printed form so a buyer who was sent the PDF and a buyer who
 * was sent the invitation link answer exactly the same questions.
 *
 * "Know Before You Buy." A "No" prompts clarification, never automatic rejection —
 * the status this class derives is a routing suggestion for the vendor's internal
 * review, not a verdict on the opportunity.
 */
class BuyerQualificationQuestionnaire
{
    /** Response sets. Most questions are Yes/No; two allow N/A (19 and 22). */
    public const YES_NO = ['yes' => 'Yes', 'no' => 'No'];

    public const YES_NO_NA = ['yes' => 'Yes', 'no' => 'No', 'na' => 'N/A'];

    /** Service types offered on the form, plus a free-text "Other". */
    public const SERVICE_TYPES = [
        'armed' => 'Armed',
        'unarmed' => 'Unarmed',
        'mobile_patrol' => 'Mobile Patrol',
        'event_security' => 'Event Security',
        'other' => 'Other',
    ];

    /** How the approved budget figure should be read. */
    public const BUDGET_BASIS = [
        'monthly' => 'Monthly',
        'annual' => 'Annual',
        'total_project' => 'Total project',
    ];

    public const CONTACT_METHODS = [
        'phone' => 'Phone',
        'text' => 'Text',
        'email' => 'Email',
        'messenger' => 'Messenger',
        'any' => 'Any of the above',
    ];

    /** Internal review outcomes, with the condition each one is for. */
    public const STATUSES = [
        'ready_for_qualification_meeting' => [
            'label' => 'Ready for qualification meeting',
            'guidance' => 'Decision-maker access, funding, basic scope, and start date are established.',
            'badge' => 'success',
        ],
        'scope_development_needed' => [
            'label' => 'Scope development needed',
            'guidance' => 'Duties, staffing, or coverage requirements need clarification.',
            'badge' => 'info',
        ],
        'hold_funding_or_approval_pending' => [
            'label' => 'Hold: funding or approval pending',
            'guidance' => 'Budget, purchasing authority, or required approvals remain unresolved.',
            'badge' => 'warning',
        ],
        'future_opportunity' => [
            'label' => 'Future opportunity',
            'guidance' => 'Start date is more than 45 days away; schedule follow-up.',
            'badge' => 'secondary',
        ],
        'urgent_feasibility_review' => [
            'label' => 'Urgent feasibility review',
            'guidance' => 'Start date leaves limited time for assessment, vendor selection, payment, and mobilization.',
            'badge' => 'danger',
        ],
    ];

    /** A start date closer than this leaves no room to assess, select, pay and mobilize. */
    public const URGENT_WINDOW_DAYS = 14;

    /** The form's own horizon for a live opportunity (question 11). */
    public const ACTIVE_WINDOW_DAYS = 45;

    /**
     * The four question sections, in form order.
     *
     * @return array<int, array{title: string, questions: array<int, array{key: string, number: string, text: string, options: array<string, string>}>}>
     */
    public static function sections(): array
    {
        return [
            [
                'title' => 'Authority and funding',
                'questions' => [
                    self::question('q1', '1', 'Are you authorized to select the security company and sign the service agreement?'),
                    self::question('q2', '2', 'Do you have authority to approve the security budget?'),
                    self::question('q3', '3', 'Has funding for the security services been approved?'),
                    self::question('q4', '4', 'Can you provide the approved budget amount for the requested coverage?'),
                    self::question('q5', '5', 'Can all required decision-makers attend a brief scope review discovery/Zoom call before an estimate is prepared?'),
                    self::question('q5a', '5a', 'Do you require 3 bids or more?'),
                ],
            ],
            [
                'title' => 'Scope and timing',
                'questions' => [
                    self::question('q6', '6', 'Is the service location confirmed?'),
                    self::question('q7', '7', 'Have you identified the duties the security personnel will perform?'),
                    self::question('q8', '8', 'Have you determined whether armed or unarmed officers are required?'),
                    self::question('q9', '9', 'Have you established the coverage hours, days, and number of officers required per shift?'),
                    self::question('q10', '10', 'Is there a confirmed target start date?'),
                    self::question('q11', '11', 'Is the project scheduled to begin within the next 45 days?'),
                    self::question('q12', '12', 'Have you established the anticipated service duration or end date?'),
                ],
            ],
            [
                'title' => 'Assessment and vendor selection',
                'questions' => [
                    self::question('q13', '13', 'Will you allow a site risk assessment before the scope and price are finalized?'),
                    self::question('q14', '14', 'Will an authorized decision-maker attend the risk assessment out-brief?'),
                    self::question('q15', '15', 'Are you willing to interview qualified vendors before selecting a provider?'),
                    self::question('q16', '16', 'Will you evaluate staffing, qualifications, and service protections alongside price?'),
                    self::question('q17', '17', 'Have you established a date for selecting the security provider?'),
                    self::question('q18', '18', 'Are competitive bidding, board approval, or other purchasing approvals required before signing an agreement?'),
                    self::question('q19', '19', 'If additional purchasing approvals are required, have they been completed?', self::YES_NO_NA),
                ],
            ],
            [
                'title' => 'Payment and transition readiness',
                'questions' => [
                    self::question('q20', '20', 'Can your organization pay the required initial invoice before services begin?'),
                    self::question('q21', '21', 'Is a security company currently providing services at this location?'),
                    self::question('q22', '22', "If replacing a provider, have you confirmed the existing contract's cancellation and notice requirements?", self::YES_NO_NA),
                    self::question('q23', '23', 'Do you require the service provider have 30-45 days of working capital?'),
                ],
            ],
        ];
    }

    /**
     * Every question, keyed by answer key.
     *
     * @return array<string, array{key: string, number: string, text: string, options: array<string, string>}>
     */
    public static function questions(): array
    {
        $questions = [];
        foreach (self::sections() as $section) {
            foreach ($section['questions'] as $question) {
                $questions[$question['key']] = $question;
            }
        }

        return $questions;
    }

    /** Validation rules for the Yes/No answers, by answer key. */
    public static function answerRules(): array
    {
        $rules = [];
        foreach (self::questions() as $key => $question) {
            $rules["answers.{$key}"] = ['required', 'string', 'in:'.implode(',', array_keys($question['options']))];
        }

        return $rules;
    }

    /** Human labels for the validator, so errors name the question rather than "answers.q7". */
    public static function answerAttributes(): array
    {
        $attributes = [];
        foreach (self::questions() as $key => $question) {
            $attributes["answers.{$key}"] = 'question '.$question['number'];
        }

        return $attributes;
    }

    /** Keep only known questions with known answers, in form order. */
    public static function normalizeAnswers(array $answers): array
    {
        $clean = [];
        foreach (self::questions() as $key => $question) {
            $value = $answers[$key] ?? null;
            if (is_string($value) && array_key_exists($value, $question['options'])) {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }

    /**
     * Suggest the internal review status from the answers and the requested start date.
     *
     * Order matters: a start date inside the mobilization window is the most urgent
     * signal even when funding is also unresolved, because that squeeze is what
     * decides whether the opportunity is workable at all.
     */
    public static function deriveStatus(array $answers, ?CarbonInterface $startDate = null): string
    {
        $answers = self::normalizeAnswers($answers);
        $answer = static fn (string $key): ?string => $answers[$key] ?? null;
        $daysToStart = $startDate === null
            ? null
            : (int) now()->startOfDay()->diffInDays($startDate->copy()->startOfDay(), false);

        $startsInsideMobilizationWindow = $daysToStart !== null
            && $daysToStart <= self::URGENT_WINDOW_DAYS;

        if ($startsInsideMobilizationWindow) {
            return 'urgent_feasibility_review';
        }

        $fundingUnresolved = in_array('no', [
            $answer('q1'), $answer('q2'), $answer('q3'), $answer('q4'), $answer('q20'),
        ], true);
        $approvalsOutstanding = $answer('q18') === 'yes' && $answer('q19') === 'no';

        if ($fundingUnresolved || $approvalsOutstanding) {
            return 'hold_funding_or_approval_pending';
        }

        $scopeIncomplete = in_array('no', [
            $answer('q6'), $answer('q7'), $answer('q8'), $answer('q9'), $answer('q12'),
        ], true);

        if ($scopeIncomplete) {
            return 'scope_development_needed';
        }

        $startNotConfirmed = $answer('q10') === 'no' || $answer('q11') === 'no';
        $startBeyondWindow = $daysToStart !== null && $daysToStart > self::ACTIVE_WINDOW_DAYS;

        if ($startNotConfirmed || $startBeyondWindow) {
            return 'future_opportunity';
        }

        return 'ready_for_qualification_meeting';
    }

    public static function statusLabel(?string $status): string
    {
        return self::STATUSES[$status ?? '']['label'] ?? 'Not assessed';
    }

    public static function statusGuidance(?string $status): string
    {
        return self::STATUSES[$status ?? '']['guidance'] ?? 'No qualification answers were recorded for this enquiry.';
    }

    public static function statusBadge(?string $status): string
    {
        return self::STATUSES[$status ?? '']['badge'] ?? 'secondary';
    }

    /** The answers paired with their question text, ready to print on a page or in an email. */
    public static function answerSummary(VendorBuyerLead $lead): array
    {
        $answers = is_array($lead->qualification_answers) ? $lead->qualification_answers : [];
        $summary = [];

        foreach (self::sections() as $section) {
            $rows = [];
            foreach ($section['questions'] as $question) {
                $value = $answers[$question['key']] ?? null;
                $rows[] = [
                    'number' => $question['number'],
                    'text' => $question['text'],
                    'answer' => $question['options'][$value] ?? 'Not answered',
                    'is_no' => $value === 'no',
                ];
            }
            $summary[] = ['title' => $section['title'], 'rows' => $rows];
        }

        return $summary;
    }

    /** The questions answered "No" — the clarification list for the vendor's follow-up. */
    public static function clarificationItems(VendorBuyerLead $lead): array
    {
        $items = [];
        foreach (self::answerSummary($lead) as $section) {
            foreach ($section['rows'] as $row) {
                if ($row['is_no']) {
                    $items[] = $row['number'].'. '.$row['text'];
                }
            }
        }

        return $items;
    }

    private static function question(string $key, string $number, string $text, ?array $options = null): array
    {
        return [
            'key' => $key,
            'number' => $number,
            'text' => $text,
            'options' => $options ?? self::YES_NO,
        ];
    }
}
