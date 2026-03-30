<?php

namespace Tests\Unit;

use App\Services\V24\InstantEstimator\InstantEstimatorComputeService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InstantEstimatorV24ParityTest extends TestCase
{
    #[DataProvider('cases')]
    public function test_instant_estimator_v24_cases_match_expected(string $caseFile): void
    {
        $in = json_decode(file_get_contents($caseFile), true, flags: JSON_THROW_ON_ERROR);
        $expectedFile = str_replace('.json', '.expected.json', $caseFile);
        $expected = json_decode(file_get_contents($expectedFile), true, flags: JSON_THROW_ON_ERROR);

        /** @var InstantEstimatorComputeService $svc */
        $svc = $this->app->make(InstantEstimatorComputeService::class);
        $out = $svc->compute($in['scenario']);

        $this->assertSame($expected['kpis'], $out['kpis']);
    }

    public static function cases(): array
    {
        $base = base_path('tests/Fixtures/v24/instant_estimator');
        return [
            'basic' => [$base.'/case_basic.json'],
        ];
    }
}

