<?php

namespace Tests\Unit;

use App\Services\V24\MainMenu\MainMenuComputeService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MainMenuV24ParityTest extends TestCase
{
    #[DataProvider('cases')]
    public function test_main_menu_v24_cases_match_expected(string $caseFile): void
    {
        $in = json_decode(file_get_contents($caseFile), true, flags: JSON_THROW_ON_ERROR);
        $expectedFile = str_replace('.json', '.expected.json', $caseFile);
        $expected = json_decode(file_get_contents($expectedFile), true, flags: JSON_THROW_ON_ERROR);

        /** @var MainMenuComputeService $svc */
        $svc = $this->app->make(MainMenuComputeService::class);
        $out = $svc->compute($in['scenario']);

        // Compare the high-signal parity surface (tabs).
        $this->assertSame($expected['tabs'], $out['tabs']);
    }

    public static function cases(): array
    {
        $base = base_path('tests/Fixtures/v24/main_menu');
        return [
            'basic' => [$base.'/case_basic.json'],
        ];
    }
}

