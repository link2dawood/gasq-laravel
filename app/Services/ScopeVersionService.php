<?php

namespace App\Services;

use App\Models\JobPosting;
use App\Models\ScopeVersion;
use App\Support\OpportunityStatus;

/**
 * Scope versioning (review spec §6, P0-15).
 *
 * A material change must not silently alter an opportunity a vendor has already accepted.
 * A vendor that agreed to 40 hours a week of unarmed cover at $20/hour has not agreed to
 * 168 hours of armed cover at $17, and the platform should not let that substitution
 * happen behind a single edit.
 *
 * Material changes bump the major version (1.0 -> 2.0) and are flagged for vendor
 * re-acknowledgement. Non-material edits bump the minor version (1.0 -> 1.1).
 */
class ScopeVersionService
{
    /**
     * Fields whose change materially alters what a vendor agreed to (§6).
     *
     * @var array<string, string>
     */
    public const MATERIAL_FIELDS = [
        'hours_per_day' => 'Hours per day',
        'days_per_week' => 'Days per week',
        'weeks_per_year' => 'Weeks per year',
        'staff_per_shift' => 'Officers per shift',
        'shifts_needed' => 'Shifts needed',
        'armed_status' => 'Armed / unarmed',
        'baseline_wage' => 'Baseline wage',
        'service_start_date' => 'Start date',
        'desired_contract_term' => 'Contract term',
        'business_address' => 'Service location',
        'insurance_minimums_required' => 'Insurance requirements',
        'duties_required' => 'Duties required',
        'service_types' => 'Service types',
    ];

    /**
     * Compare two scope payloads and describe what moved.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return list<array{field:string,label:string,from:mixed,to:mixed,material:bool}>
     */
    public function diff(array $before, array $after): array
    {
        $changes = [];

        foreach (self::MATERIAL_FIELDS as $field => $label) {
            $from = $before[$field] ?? null;
            $to = $after[$field] ?? null;

            if ($this->normalise($from) === $this->normalise($to)) {
                continue;
            }

            $changes[] = [
                'field' => $field,
                'label' => $label,
                'from' => $from,
                'to' => $to,
                'material' => true,
            ];
        }

        return $changes;
    }

    /** Does this change require a new major version and vendor re-acknowledgement? */
    public function isMaterialChange(array $before, array $after): bool
    {
        return $this->diff($before, $after) !== [];
    }

    /**
     * Next version string.
     *
     * Material changes bump the major component and reset the minor, so "2.0" reads
     * unmistakably as a different deal from "1.3" rather than one more small edit.
     */
    public function nextVersion(string $current, bool $material): string
    {
        [$major, $minor] = array_pad(array_map('intval', explode('.', $current, 2)), 2, 0);

        return $material
            ? ($major + 1) . '.0'
            : $major . '.' . ($minor + 1);
    }

    /**
     * Record a scope change against an opportunity, bumping the version.
     *
     * Returns null when nothing tracked actually changed — an edit that touches only
     * cosmetic fields should not manufacture a version.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function record(JobPosting $job, array $before, array $after, ?int $changedBy = null, ?string $note = null): ?ScopeVersion
    {
        $changes = $this->diff($before, $after);

        if ($changes === []) {
            return null;
        }

        // Only an opportunity vendors can already see needs the stronger signal; before
        // release a change is simply an edit.
        $material = OpportunityStatus::isReleased($job->opportunity_status);

        $version = $this->nextVersion((string) ($job->scope_version ?: '1.0'), $material);

        $record = ScopeVersion::create([
            'job_posting_id' => $job->id,
            'version' => $version,
            'is_material' => $material,
            'changes' => $changes,
            'changed_by' => $changedBy,
            'note' => $note,
        ]);

        $job->forceFill(['scope_version' => $version])->save();

        return $record;
    }

    /** Normalise for comparison so 8 vs "8" and reordered arrays are not false positives. */
    private function normalise(mixed $value): string
    {
        if (is_array($value)) {
            $copy = $value;
            sort($copy);

            return json_encode(array_map(fn ($v) => is_scalar($v) ? (string) $v : $v, $copy)) ?: '';
        }

        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return trim((string) $value);
    }
}
