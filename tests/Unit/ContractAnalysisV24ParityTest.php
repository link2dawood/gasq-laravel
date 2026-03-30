<?php

namespace Tests\Unit;

use App\Services\V24\ContractAnalysis\ContractAnalysisV24ComputeService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ContractAnalysisV24ParityTest extends TestCase
{
    #[DataProvider('cases')]
    public function test_contract_analysis_v24_cases_match_expected(string $caseFile): void
    {
        $in = json_decode(file_get_contents($caseFile), true, flags: JSON_THROW_ON_ERROR);
        $expectedFile = str_replace('.json', '.expected.json', $caseFile);
        $expected = json_decode(file_get_contents($expectedFile), true, flags: JSON_THROW_ON_ERROR);

        /** @var ContractAnalysisV24ComputeService $svc */
        $svc = $this->app->make(ContractAnalysisV24ComputeService::class);
        $out = $svc->compute($in['scenario']);

        $this->assertSame($expected['footers'], $out['footers']);
        $this->assertSame($expected['perHour'], $out['perHour']);
        $this->assertSame($expected['summary'], $out['summary']);
    }

    public static function cases(): array
    {
        $base = base_path('tests/Fixtures/v24/contract_analysis');
        return [
            'basic' => [$base.'/case_basic.json'],
        ];
    }
}

