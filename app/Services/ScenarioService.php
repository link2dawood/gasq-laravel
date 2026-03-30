<?php

namespace App\Services;

use App\Models\Scenario;
use App\Models\ScenarioPost;
use App\Models\ScenarioScopeRequirement;
use App\Models\ScenarioShift;
use App\Models\ScenarioSite;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ScenarioService
{
    /**
     * Normalize scope keys from API (camelCase or snake_case) to internal float fields.
     *
     * @return array{hours_of_coverage_per_day: float, days_of_coverage_per_week: float, weeks_of_coverage: float, staff_per_8hr_shift: float, notes: ?string}
     */
    public function normalizeScopeInput(array $scope): array
    {
        $hours = Arr::get($scope, 'hoursOfCoveragePerDay', Arr::get($scope, 'hours_of_coverage_per_day'));
        $days = Arr::get($scope, 'daysOfCoveragePerWeek', Arr::get($scope, 'days_of_coverage_per_week'));
        $weeks = Arr::get($scope, 'weeksOfCoverage', Arr::get($scope, 'weeks_of_coverage'));
        $staff = Arr::get($scope, 'staffPerShift', Arr::get($scope, 'staff_per_8hr_shift'));
        $notes = Arr::get($scope, 'notes');

        return [
            'hours_of_coverage_per_day' => (float) $hours,
            'days_of_coverage_per_week' => (float) $days,
            'weeks_of_coverage' => (float) $weeks,
            'staff_per_8hr_shift' => (float) $staff,
            'notes' => $notes !== null ? (string) $notes : null,
        ];
    }

    /**
     * Validate scope block (Post_Positions X27–X30 / ScopeInputs parity).
     *
     * @throws ValidationException
     */
    public function validateScope(array $scope): void
    {
        $n = $this->normalizeScopeInput($scope);

        $validator = Validator::make($n, [
            'hours_of_coverage_per_day' => ['required', 'numeric', 'between:0,24'],
            'days_of_coverage_per_week' => ['required', 'numeric', 'between:0.01,7'],
            'weeks_of_coverage' => ['required', 'numeric', 'between:0.01,52'],
            'staff_per_8hr_shift' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:65535'],
        ], [], [
            'hours_of_coverage_per_day' => 'hours of coverage per day',
            'days_of_coverage_per_week' => 'days of coverage per week',
            'weeks_of_coverage' => 'weeks of coverage',
            'staff_per_8hr_shift' => 'staff per 8-hour shift',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Derive annual coverage / labor hours and indicative FTEs (see SPREADSHEET_CELL_MAPPING § B.1 derivatives).
     *
     * @param  array<int, array<string, mixed>>  $posts
     * @param  array<string, mixed>  $scope  normalized or raw scope (normalized inside)
     * @param  array<string, mixed>  $assumptions  matches `assumptions.*` keys from Inputs sheet
     * @return array<string, mixed>
     */
    public function deriveRequiredHours(array $posts, array $scope, array $assumptions = []): array
    {
        $s = $this->normalizeScopeInput($scope);
        $annualPaidHoursPerFte = (float) (Arr::get($assumptions, 'annualPaidHoursPerFTE')
            ?? Arr::get($assumptions, 'annual_paid_hours_per_fte')
            ?? 2080);

        $h = $s['hours_of_coverage_per_day'];
        $d = $s['days_of_coverage_per_week'];
        $w = min($s['weeks_of_coverage'], 52);
        $staff = $s['staff_per_8hr_shift'];

        // Mirrors spreadsheet intent: coverage hours scale with staffed shifts and operating weeks.
        $annualCoverageHours = $h * $d * $w * $staff;

        $totalWeeklyPostHours = 0.0;
        foreach ($posts as $p) {
            $qty = max(1, (int) (Arr::get($p, 'qtyRequired') ?? Arr::get($p, 'qty_required') ?? 1));
            $wh = (float) (Arr::get($p, 'weeklyHours') ?? Arr::get($p, 'weekly_hours') ?? 0);
            $totalWeeklyPostHours += $qty * $wh;
        }

        $annualLaborHours = $totalWeeklyPostHours * $w;

        $ftesPaidBasis = $annualPaidHoursPerFte > 0
            ? $annualLaborHours / $annualPaidHoursPerFte
            : 0.0;

        return [
            'annualCoverageHours' => round($annualCoverageHours, 2),
            'annualLaborHours' => round($annualLaborHours, 2),
            'totalWeeklyPostHours' => round($totalWeeklyPostHours, 2),
            'ftesRequiredAtPaidHoursBasis' => round($ftesPaidBasis, 4),
            'annualPaidHoursPerFteUsed' => $annualPaidHoursPerFte,
        ];
    }

    /**
     * Map posts to coverage totals and per-line allocation (for UI / downstream engines).
     *
     * @return array{derived: array<string, mixed>, posts: array<int, array<string, mixed>>}
     */
    public function mapPostsToCoverage(Scenario $scenario): array
    {
        $scenario->loadMissing(['posts', 'coverageScope']);
        $assumptions = $scenario->assumptions ?? [];

        if (! $scenario->coverageScope) {
            return [
                'derived' => [],
                'posts' => [],
            ];
        }

        $scope = $scenario->coverageScope->toScopeArray();
        $postRows = $scenario->posts->map(function (ScenarioPost $p) {
            return [
                'qty_required' => $p->qty_required,
                'weekly_hours' => (float) $p->weekly_hours,
            ];
        })->all();

        $derived = $this->deriveRequiredHours($postRows, $scope, $assumptions);
        $derived['scope'] = $scope;

        $totalWeekly = (float) $derived['totalWeeklyPostHours'];
        $lines = [];

        foreach ($scenario->posts as $post) {
            $qty = max(1, (int) $post->qty_required);
            $weekly = (float) $post->weekly_hours;
            $lineWeekly = $qty * $weekly;
            $lines[] = [
                'scenario_post_id' => $post->id,
                'qty' => $qty,
                'weeklyHoursPerPosition' => $weekly,
                'weeklyHoursTotal' => round($lineWeekly, 2),
                'shareOfTotalWeeklyPostHoursPct' => $totalWeekly > 0
                    ? round(100 * $lineWeekly / $totalWeekly, 2)
                    : 0.0,
            ];
        }

        return [
            'derived' => $derived,
            'posts' => $lines,
        ];
    }

    public function refreshCoverageSnapshot(Scenario $scenario): void
    {
        $snapshot = $this->mapPostsToCoverage($scenario);
        $scenario->update([
            'coverage_snapshot' => $snapshot,
        ]);
    }

    /**
     * Full scenario graph for calculators / parity engines.
     *
     * @return array<string, mixed>
     */
    public function toPayload(Scenario $scenario): array
    {
        $scenario->load(['sites', 'posts.site', 'coverageScope', 'shifts']);

        return [
            'id' => $scenario->id,
            'title' => $scenario->title,
            'status' => $scenario->status,
            'workbookVersion' => $scenario->workbook_version,
            'assumptions' => $scenario->assumptions ?? new \stdClass,
            'vehicle' => $scenario->vehicle ?? new \stdClass,
            'meta' => $scenario->meta ?? new \stdClass,
            'sites' => $scenario->sites->map(fn (ScenarioSite $s) => [
                'id' => $s->id,
                'sortOrder' => $s->sort_order,
                'name' => $s->name,
                'addressLine1' => $s->address_line1,
                'city' => $s->city,
                'state' => $s->state,
                'zip' => $s->zip,
                'country' => $s->country,
                'latitude' => $s->latitude !== null ? (float) $s->latitude : null,
                'longitude' => $s->longitude !== null ? (float) $s->longitude : null,
                'googlePlaceId' => $s->google_place_id,
            ])->values()->all(),
            'posts' => $scenario->posts->map(fn (ScenarioPost $p) => [
                'id' => $p->id,
                'scenarioSiteId' => $p->scenario_site_id,
                'sortOrder' => $p->sort_order,
                'postName' => $p->post_name,
                'positionTitle' => $p->position_title,
                'location' => $p->location_text,
                'qtyRequired' => $p->qty_required,
                'weeklyHours' => (float) $p->weekly_hours,
                'rateMode' => $p->pay_rate_mode,
                'wageMode' => $p->wage_mode,
                'manualPayWage' => $p->manual_pay_wage !== null ? (float) $p->manual_pay_wage : null,
                'manualBillRate' => $p->manual_bill_rate !== null ? (float) $p->manual_bill_rate : null,
            ])->values()->all(),
            'scope' => $scenario->coverageScope?->toScopeArray(),
            'shifts' => $scenario->shifts->map(fn (ScenarioShift $sh) => [
                'id' => $sh->id,
                'scenarioPostId' => $sh->scenario_post_id,
                'sortOrder' => $sh->sort_order,
                'label' => $sh->label,
                'hoursPerWeek' => (float) $sh->hours_per_week,
                'pattern' => $sh->pattern,
            ])->values()->all(),
            'coverage' => $scenario->coverage_snapshot,
        ];
    }

    public function createScenario(User $user, array $payload): Scenario
    {
        $scope = $payload['scope'] ?? null;
        if (! is_array($scope)) {
            throw ValidationException::withMessages(['scope' => ['Scope block is required.']]);
        }
        $this->validateScope($scope);

        return DB::transaction(function () use ($user, $payload, $scope) {
            /** @var Scenario $scenario */
            $scenario = Scenario::create([
                'user_id' => $user->id,
                'title' => $payload['title'] ?? null,
                'status' => $payload['status'] ?? 'draft',
                'workbook_version' => $payload['workbook_version'] ?? $payload['workbookVersion'] ?? 'V24',
                'assumptions' => $payload['assumptions'] ?? [],
                'vehicle' => $payload['vehicle'] ?? null,
                'meta' => $payload['meta'] ?? null,
            ]);

            $siteIdsByIndex = $this->syncSites($scenario, $payload['sites'] ?? []);
            $this->syncPosts($scenario, $payload['posts'] ?? [], $siteIdsByIndex);
            $this->persistScope($scenario, $scope);
            $postIdsByIndex = $scenario->posts()->orderBy('sort_order')->orderBy('id')->pluck('id')->values()->all();
            $this->syncShifts($scenario, $payload['shifts'] ?? [], $postIdsByIndex);

            $this->refreshCoverageSnapshot($scenario->fresh(['posts', 'coverageScope']));

            return $scenario->fresh(['sites', 'posts', 'coverageScope', 'shifts']);
        });
    }

    public function updateScenario(Scenario $scenario, array $payload): Scenario
    {
        if (isset($payload['scope']) && is_array($payload['scope'])) {
            $this->validateScope($payload['scope']);
        }

        return DB::transaction(function () use ($scenario, $payload) {
            $scalar = [];
            foreach (['title', 'status', 'assumptions', 'vehicle', 'meta'] as $key) {
                if (array_key_exists($key, $payload)) {
                    $scalar[$key] = $payload[$key];
                }
            }
            if (array_key_exists('workbook_version', $payload)) {
                $scalar['workbook_version'] = $payload['workbook_version'];
            }
            if (array_key_exists('workbookVersion', $payload)) {
                $scalar['workbook_version'] = $payload['workbookVersion'];
            }
            if ($scalar !== []) {
                $scenario->update($scalar);
            }

            $siteIdsByIndex = $scenario->sites()->orderBy('sort_order')->orderBy('id')->pluck('id')->values()->all();

            if (array_key_exists('sites', $payload)) {
                $scenario->sites()->delete();
                $siteIdsByIndex = $this->syncSites($scenario, $payload['sites'] ?? []);
            }

            if (array_key_exists('posts', $payload)) {
                $scenario->shifts()->delete();
                $scenario->posts()->delete();
                $this->syncPosts($scenario, $payload['posts'] ?? [], $siteIdsByIndex);
                $postIdsByIndex = $scenario->posts()->orderBy('sort_order')->orderBy('id')->pluck('id')->values()->all();
                $this->syncShifts($scenario, $payload['shifts'] ?? [], $postIdsByIndex);
            } elseif (array_key_exists('shifts', $payload)) {
                $postIdsByIndex = $scenario->posts()->orderBy('sort_order')->orderBy('id')->pluck('id')->values()->all();
                $scenario->shifts()->delete();
                $this->syncShifts($scenario, $payload['shifts'] ?? [], $postIdsByIndex);
            }

            if (isset($payload['scope']) && is_array($payload['scope'])) {
                $scenario->coverageScope()->delete();
                $this->persistScope($scenario, $payload['scope']);
            }

            $this->refreshCoverageSnapshot($scenario->fresh(['posts', 'coverageScope']));

            return $scenario->fresh(['sites', 'posts', 'coverageScope', 'shifts']);
        });
    }

    /**
     * @param  array<int, mixed>  $sites
     * @return list<int|string>
     */
    private function syncSites(Scenario $scenario, array $sites): array
    {
        $ids = [];
        foreach (array_values($sites) as $i => $site) {
            if (! is_array($site)) {
                continue;
            }
            $row = ScenarioSite::create([
                'scenario_id' => $scenario->id,
                'sort_order' => (int) ($site['sort_order'] ?? $site['sortOrder'] ?? $i),
                'name' => $site['name'] ?? null,
                'address_line1' => $site['address_line1'] ?? $site['addressLine1'] ?? null,
                'city' => $site['city'] ?? null,
                'state' => $site['state'] ?? null,
                'zip' => $site['zip'] ?? null,
                'country' => $site['country'] ?? null,
                'latitude' => isset($site['latitude']) ? (float) $site['latitude'] : null,
                'longitude' => isset($site['longitude']) ? (float) $site['longitude'] : null,
                'google_place_id' => $site['google_place_id'] ?? $site['googlePlaceId'] ?? null,
            ]);
            $ids[] = $row->id;
        }

        return $ids;
    }

    /**
     * @param  array<int, mixed>  $posts
     * @param  list<int|string>  $siteIdsByIndex
     */
    private function syncPosts(Scenario $scenario, array $posts, array $siteIdsByIndex): void
    {
        foreach (array_values($posts) as $i => $post) {
            if (! is_array($post)) {
                continue;
            }
            $siteIdx = $post['site_index'] ?? $post['siteIndex'] ?? null;
            $scenarioSiteId = $post['scenario_site_id'] ?? $post['scenarioSiteId'] ?? null;
            if ($scenarioSiteId === null && $siteIdx !== null && isset($siteIdsByIndex[(int) $siteIdx])) {
                $scenarioSiteId = $siteIdsByIndex[(int) $siteIdx];
            }

            ScenarioPost::create([
                'scenario_id' => $scenario->id,
                'scenario_site_id' => $scenarioSiteId,
                'sort_order' => (int) ($post['sort_order'] ?? $post['sortOrder'] ?? $i),
                'post_name' => $post['post_name'] ?? $post['postName'] ?? null,
                'position_title' => $post['position_title'] ?? $post['positionTitle'] ?? null,
                'location_text' => $post['location_text'] ?? $post['location'] ?? null,
                'qty_required' => max(1, (int) ($post['qty_required'] ?? $post['qtyRequired'] ?? 1)),
                'weekly_hours' => (float) ($post['weekly_hours'] ?? $post['weeklyHours'] ?? 0),
                'pay_rate_mode' => $post['pay_rate_mode'] ?? $post['rateMode'] ?? 'AUTO',
                'wage_mode' => $post['wage_mode'] ?? $post['wageMode'] ?? 'AUTO',
                'manual_pay_wage' => isset($post['manual_pay_wage']) ? (float) $post['manual_pay_wage'] : (isset($post['manualPayWage']) ? (float) $post['manualPayWage'] : null),
                'manual_bill_rate' => isset($post['manual_bill_rate']) ? (float) $post['manual_bill_rate'] : (isset($post['manualBillRate']) ? (float) $post['manualBillRate'] : null),
            ]);
        }
    }

    private function persistScope(Scenario $scenario, array $scope): void
    {
        $n = $this->normalizeScopeInput($scope);
        ScenarioScopeRequirement::create([
            'scenario_id' => $scenario->id,
            'hours_coverage_per_day' => $n['hours_of_coverage_per_day'],
            'days_coverage_per_week' => $n['days_of_coverage_per_week'],
            'weeks_of_coverage' => $n['weeks_of_coverage'],
            'staff_per_8hr_shift' => $n['staff_per_8hr_shift'],
            'notes' => $n['notes'],
        ]);
    }

    /**
     * @param  array<int, mixed>  $shifts
     * @param  list<int|string>|null  $postIdsByIndex  posts in payload order (0-based) when IDs are not yet known
     */
    private function syncShifts(Scenario $scenario, array $shifts, ?array $postIdsByIndex = null): void
    {
        foreach (array_values($shifts) as $i => $shift) {
            if (! is_array($shift)) {
                continue;
            }
            $postId = $shift['scenario_post_id'] ?? $shift['scenarioPostId'] ?? null;
            if ($postId === null) {
                $postIdx = $shift['post_index'] ?? $shift['postIndex'] ?? null;
                if ($postIdx !== null && is_array($postIdsByIndex) && isset($postIdsByIndex[(int) $postIdx])) {
                    $postId = $postIdsByIndex[(int) $postIdx];
                }
            }
            ScenarioShift::create([
                'scenario_id' => $scenario->id,
                'scenario_post_id' => $postId,
                'sort_order' => (int) ($shift['sort_order'] ?? $shift['sortOrder'] ?? $i),
                'label' => $shift['label'] ?? null,
                'hours_per_week' => (float) ($shift['hours_per_week'] ?? $shift['hoursPerWeek'] ?? 0),
                'pattern' => $shift['pattern'] ?? null,
            ]);
        }
    }
}
