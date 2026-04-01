<?php

namespace Tests\Unit;

use App\Services\V24\Standalone\StandaloneV24ComputeService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StandaloneV24ParityTest extends TestCase
{
    #[DataProvider('cases')]
    public function test_standalone_v24_cases_match_expected(string $type, string $caseFile): void
    {
        $in = json_decode(file_get_contents($caseFile), true, flags: JSON_THROW_ON_ERROR);
        $expectedFile = str_replace('.json', '.expected.json', $caseFile);
        $expected = json_decode(file_get_contents($expectedFile), true, flags: JSON_THROW_ON_ERROR);

        /** @var StandaloneV24ComputeService $svc */
        $svc = $this->app->make(StandaloneV24ComputeService::class);
        $out = $svc->compute($type, $in['scenario']);

        $this->assertEquals($expected['kpis'], $out['kpis']);
    }

    public static function cases(): array
    {
        $base = base_path('tests/Fixtures/v24/standalone');
        return [
            'cost-analysis' => ['cost-analysis', $base.'/cost-analysis.basic.json'],
            'manpower-hours' => ['manpower-hours', $base.'/manpower-hours.basic.json'],
            'bill-rate-analysis' => ['bill-rate-analysis', $base.'/bill-rate-analysis.basic.json'],
            'economic-justification' => ['economic-justification', $base.'/economic-justification.basic.json'],
            'hourly-pay' => ['hourly-pay-calculator', $base.'/hourly-pay-calculator.basic.json'],
            'budget' => ['budget-calculator', $base.'/budget-calculator.basic.json'],
            'mobile-patrol-analysis' => ['mobile-patrol-analysis', $base.'/mobile-patrol-analysis.basic.json'],
            'global-security-pricing' => ['global-security-pricing', $base.'/global-security-pricing.basic.json'],
            'gasq-tco-calculator' => ['gasq-tco-calculator', $base.'/gasq-tco-calculator.basic.json'],
            'absorbed-rate-calculator' => ['absorbed-rate-calculator', $base.'/absorbed-rate-calculator.basic.json'],
            'government-contract-calculator' => ['government-contract-calculator', $base.'/government-contract-calculator.basic.json'],
            'keeps-doors-open-calculator' => ['keeps-doors-open-calculator', $base.'/keeps-doors-open-calculator.basic.json'],
            'unarmed-security-guard-services' => ['unarmed-security-guard-services', $base.'/unarmed-security-guard-services.basic.json'],
            'gasq-direct-labor-build-up' => ['gasq-direct-labor-build-up', $base.'/gasq-direct-labor-build-up.basic.json'],
            'gasq-additional-cost-stack' => ['gasq-additional-cost-stack', $base.'/gasq-additional-cost-stack.basic.json'],
        ];
    }
}

