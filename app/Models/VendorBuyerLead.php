<?php

namespace App\Models;

use App\Support\BuyerQualificationQuestionnaire;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorBuyerLead extends Model
{
    public const STATUSES = ['new' => 'New', 'contacted' => 'Contacted', 'qualified' => 'Qualified', 'won' => 'Won', 'lost' => 'Lost'];

    protected $fillable = [
        'vendor_id', 'contact_name', 'contact_title', 'company', 'email', 'phone', 'site_address',
        'decision_maker_name', 'decision_maker_phone', 'decision_maker_email',
        'approved_budget', 'approved_budget_basis', 'service_type', 'service_type_other',
        'industry', 'preferred_contact_method', 'scope', 'start_date', 'service_end_date',
        'hours_per_day', 'days_per_week', 'officers_per_shift', 'number_of_weeks', 'weekly_hours',
        'qualification_answers', 'qualification_status', 'qualification_submitted_at', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'service_end_date' => 'date',
            'weekly_hours' => 'decimal:2',
            'hours_per_day' => 'decimal:2',
            'days_per_week' => 'decimal:2',
            'officers_per_shift' => 'integer',
            'number_of_weeks' => 'integer',
            'approved_budget' => 'decimal:2',
            'qualification_answers' => 'array',
            'qualification_submitted_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function estimates(): HasMany
    {
        return $this->hasMany(VendorLeadEstimate::class);
    }

    /** The internal review outcome the qualification answers point to. */
    public function qualificationStatusLabel(): string
    {
        return BuyerQualificationQuestionnaire::statusLabel($this->qualification_status);
    }

    public function qualificationStatusGuidance(): string
    {
        return BuyerQualificationQuestionnaire::statusGuidance($this->qualification_status);
    }

    public function qualificationStatusBadge(): string
    {
        return BuyerQualificationQuestionnaire::statusBadge($this->qualification_status);
    }

    /** Service types as the buyer selected them, with any free-text "Other" spelled out. */
    public function serviceTypeLabel(): string
    {
        $label = (string) $this->service_type;
        if ($this->service_type_other) {
            $label = trim($label.' ('.$this->service_type_other.')');
        }

        return $label !== '' ? $label : 'Not supplied';
    }

    /** "$12,000 monthly": the figure means nothing without the basis the buyer chose. */
    public function approvedBudgetLabel(): string
    {
        if ($this->approved_budget === null) {
            return 'Not supplied';
        }

        $basis = BuyerQualificationQuestionnaire::BUDGET_BASIS[$this->approved_budget_basis] ?? null;

        return '$'.number_format((float) $this->approved_budget, 2).($basis ? ' '.strtolower($basis) : '');
    }

    /** Coverage as one line: hours per day, days per week, officers per shift, weeks. */
    public function coverageLabel(): string
    {
        $parts = array_filter([
            $this->hours_per_day !== null ? rtrim(rtrim((string) $this->hours_per_day, '0'), '.').' hours/day' : null,
            $this->days_per_week !== null ? rtrim(rtrim((string) $this->days_per_week, '0'), '.').' days/week' : null,
            $this->officers_per_shift !== null ? $this->officers_per_shift.' officer(s)/shift' : null,
            $this->number_of_weeks !== null ? $this->number_of_weeks.' week(s)' : null,
        ]);

        return $parts === [] ? 'Not supplied' : implode(' · ', $parts);
    }
}
