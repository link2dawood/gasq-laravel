<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculatorAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_is_redirected_from_vendor_only_standalone_calculator(): void
    {
        $buyer = User::factory()->create([
            'user_type' => 'buyer',
            'phone_verified' => true,
        ]);

        $this->actingAs($buyer)
            ->get(route('government-contract-calculator.index'))
            ->assertRedirect(route('instant-estimator.index'));
    }

    public function test_vendor_can_open_vendor_only_standalone_calculator(): void
    {
        $vendor = User::factory()->create([
            'user_type' => 'vendor',
            'phone_verified' => true,
        ]);

        $this->actingAs($vendor)
            ->get(route('government-contract-calculator.index'))
            ->assertOk();
    }
}
