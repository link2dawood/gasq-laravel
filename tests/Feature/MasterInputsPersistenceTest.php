<?php

namespace Tests\Feature;

use App\Models\MasterInputProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterInputsPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_inputs_update_persists_to_database_and_is_available_to_calculator_pages(): void
    {
        $user = User::factory()->create([
            'phone' => null,
            'phone_verified' => true,
        ]);

        $payload = [
            'inputs' => [
                'directLaborWage' => 31.5,
                'ficaMedicarePct' => 0.081,
                'futaPct' => 0.009,
                'sutaPct' => 0.031,
                'corporateOverheadPct' => 0.125,
                'profitFeePct' => 0.185,
            ],
            'is_complete' => true,
        ];

        $this->actingAs($user)
            ->putJson(route('api.master-inputs.update'), $payload)
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'is_complete' => true,
            ]);

        $this->assertDatabaseHas('master_input_profiles', [
            'user_id' => $user->id,
            'is_complete' => true,
        ]);

        $profile = MasterInputProfile::query()->where('user_id', $user->id)->first();

        $this->assertNotNull($profile);
        $this->assertSame(31.5, data_get($profile?->inputs, 'directLaborWage'));
        $this->assertSame(0.185, data_get($profile?->inputs, 'profitFeePct'));

        $this->actingAs($user)
            ->getJson(route('api.master-inputs.show'))
            ->assertOk()
            ->assertJsonPath('inputs.directLaborWage', 31.5)
            ->assertJsonPath('inputs.profitFeePct', 0.185);

        $response = $this->actingAs($user)->get(route('government-contract-calculator.index'));

        $response->assertOk();
        $response->assertSee('window.__gasqMasterInputs', false);
        $response->assertSee('"directLaborWage":31.5', false);
        $response->assertSee('"profitFeePct":0.185', false);
    }
}
