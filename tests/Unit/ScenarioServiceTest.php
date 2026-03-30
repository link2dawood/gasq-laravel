<?php

namespace Tests\Unit;

use App\Services\ScenarioService;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ScenarioServiceTest extends TestCase
{
    public function test_derive_required_hours_matches_expected_shape(): void
    {
        /** @var ScenarioService $svc */
        $svc = $this->app->make(ScenarioService::class);

        $out = $svc->deriveRequiredHours(
            [
                ['qty_required' => 2, 'weekly_hours' => 40],
                ['qty_required' => 1, 'weekly_hours' => 20],
            ],
            [
                'hoursOfCoveragePerDay' => 8,
                'daysOfCoveragePerWeek' => 5,
                'weeksOfCoverage' => 52,
                'staffPerShift' => 1,
            ],
            ['annualPaidHoursPerFTE' => 2080]
        );

        $this->assertSame(2080.0, $out['annualCoverageHours']);
        $this->assertSame(100.0, $out['totalWeeklyPostHours']);
        $this->assertSame(5200.0, $out['annualLaborHours']);
        $this->assertSame(2.5, $out['ftesRequiredAtPaidHoursBasis']);
    }

    #[DataProvider('invalidScopes')]
    public function test_validate_scope_rejects_invalid(array $scope): void
    {
        /** @var ScenarioService $svc */
        $svc = $this->app->make(ScenarioService::class);

        $this->expectException(ValidationException::class);
        $svc->validateScope($scope);
    }

    public static function invalidScopes(): array
    {
        return [
            'hours over 24' => [[
                'hoursOfCoveragePerDay' => 25,
                'daysOfCoveragePerWeek' => 5,
                'weeksOfCoverage' => 52,
                'staffPerShift' => 1,
            ]],
            'zero staff' => [[
                'hoursOfCoveragePerDay' => 8,
                'daysOfCoveragePerWeek' => 5,
                'weeksOfCoverage' => 52,
                'staffPerShift' => 0,
            ]],
        ];
    }
}
