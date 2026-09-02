<?php

namespace Tests\Feature;

use App\Mail\EstimateFollowUpMail;
use App\Models\User;
use App\Services\EstimateFollowUpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EstimateFollowUpTest extends TestCase
{
    use RefreshDatabase;

    public function test_follow_up_sends_once_per_distinct_estimate(): void
    {
        Mail::fake();
        $buyer = User::factory()->create(['user_type' => 'buyer']);
        $scenario = ['meta' => ['requesterEmail' => $buyer->email, 'location' => 'Atlanta, GA']];
        $service = app(EstimateFollowUpService::class);

        $first = $service->sendFor($buyer, $scenario, ['outsourcedAnnual' => 120000]);
        $again = $service->sendFor($buyer, $scenario, ['outsourcedAnnual' => 120000]);
        $newEstimate = $service->sendFor($buyer, $scenario, ['outsourcedAnnual' => 125000]);

        $this->assertNotNull($first);
        $this->assertSame($first?->id, $again?->id);
        $this->assertNotSame($first?->id, $newEstimate?->id);
        Mail::assertSent(EstimateFollowUpMail::class, 2);
    }
}
