<?php

namespace Tests\Unit;

use App\Services\SecurityBillingService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SecurityBillingParityTest extends TestCase
{
    #[DataProvider('cases')]
    public function test_security_billing_totals_match_expected(array $input, array $expected): void
    {
        /** @var SecurityBillingService $svc */
        $svc = $this->app->make(SecurityBillingService::class);

        $out = $svc->calculate(
            (float) $input['hourly_rate'],
            (float) $input['hours_per_week'],
            (int) $input['weeks']
        );

        $this->assertSame($expected['weekly_total'], $out['weekly_total']);
        $this->assertSame($expected['monthly_total'], $out['monthly_total']);
        $this->assertSame($expected['annual_total'], $out['annual_total']);
    }

    public static function cases(): array
    {
        return [
            'typical' => [
                'input' => ['hourly_rate' => 45, 'hours_per_week' => 40, 'weeks' => 52],
                'expected' => [
                    'weekly_total' => 1800.00,
                    'monthly_total' => 7794.00, // 1800 * 4.33
                    'annual_total' => 93600.00, // 1800 * 52
                ],
            ],
            'fractional hours' => [
                'input' => ['hourly_rate' => 62.9, 'hours_per_week' => 37.5, 'weeks' => 52],
                'expected' => [
                    'weekly_total' => 2358.75,
                    'monthly_total' => 10213.39, // round(2358.75 * 4.33, 2)
                    'annual_total' => 122655.00,
                ],
            ],
            'different weeks' => [
                'input' => ['hourly_rate' => 30, 'hours_per_week' => 56, 'weeks' => 26],
                'expected' => [
                    'weekly_total' => 1680.00,
                    'monthly_total' => 7274.40,
                    'annual_total' => 43680.00,
                ],
            ],
        ];
    }
}

