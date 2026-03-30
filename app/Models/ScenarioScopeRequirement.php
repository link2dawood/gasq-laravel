<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScenarioScopeRequirement extends Model
{
    protected $fillable = [
        'scenario_id',
        'hours_coverage_per_day',
        'days_coverage_per_week',
        'weeks_of_coverage',
        'staff_per_8hr_shift',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'hours_coverage_per_day' => 'decimal:2',
            'days_coverage_per_week' => 'decimal:2',
            'weeks_of_coverage' => 'decimal:2',
            'staff_per_8hr_shift' => 'decimal:2',
        ];
    }

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class);
    }

    /**
     * Normalized scope block aligned with docs/SPREADSHEET_CELL_MAPPING (B) ScopeInputs.
     *
     * @return array<string, float|int|string|null>
     */
    public function toScopeArray(): array
    {
        return [
            'hoursOfCoveragePerDay' => (float) $this->hours_coverage_per_day,
            'daysOfCoveragePerWeek' => (float) $this->days_coverage_per_week,
            'weeksOfCoverage' => (float) $this->weeks_of_coverage,
            'staffPerShift' => (float) $this->staff_per_8hr_shift,
            'notes' => $this->notes,
        ];
    }
}
