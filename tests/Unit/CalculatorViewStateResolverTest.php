<?php

namespace Tests\Unit;

use App\Services\CalculatorViewStateResolver;
use Tests\TestCase;

class CalculatorViewStateResolverTest extends TestCase
{
    public function test_it_maps_calculator_routes_to_saved_state_types(): void
    {
        $resolver = new CalculatorViewStateResolver();

        $this->assertSame('budget-calculator', $resolver->resolveType('budget-calculator.index'));
        $this->assertSame('security-billing', $resolver->resolveType('security-billing.index'));
        $this->assertSame('mobile-patrol', $resolver->resolveType('mobile-patrol-calculator'));
        $this->assertSame('mobile-patrol-comparison', $resolver->resolveType('mobile-patrol-comparison'));
        $this->assertSame('workforce-appraisal-report', $resolver->resolveType('cfo-bill-rate-breakdown.index'));
        $this->assertSame('workforce-appraisal-report', $resolver->resolveType('post-position-summary.index'));
        $this->assertSame('workforce-appraisal-report', $resolver->resolveType('appraisal-comparison-summary.index'));
        $this->assertSame('gasq-direct-labor-build-up', $resolver->resolveType('gasq-direct-labor-build-up.index'));
        $this->assertSame('gasq-additional-cost-stack', $resolver->resolveType('gasq-additional-cost-stack.index'));
    }

    public function test_it_returns_null_for_non_calculator_routes(): void
    {
        $resolver = new CalculatorViewStateResolver();

        $this->assertNull($resolver->resolveType('home'));
        $this->assertNull($resolver->resolveType(null));
    }
}
