<?php

namespace Tests\Feature;

use App\Models\JobPosting;
use App\Models\User;
use App\Services\ScopeVersionService;
use App\Support\OpportunityStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Scope versioning (review spec §6, P0-15) and the opportunity lifecycle (§28).
 *
 * The point: a material change must not silently alter an opportunity a vendor has
 * already accepted.
 */
class ScopeVersioningTest extends TestCase
{
    use RefreshDatabase;

    private ScopeVersionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ScopeVersionService();
    }

    private function buyer(): User
    {
        return User::create([
            'name' => 'Buyer',
            'email' => 'buyer' . uniqid() . '@example.test',
            'password' => Hash::make('password'),
            'user_type' => 'buyer',
        ]);
    }

    private function job(User $buyer, string $status = OpportunityStatus::OPEN_TO_VENDORS): JobPosting
    {
        return JobPosting::create([
            'user_id' => $buyer->id,
            'title' => 'Unarmed cover — Atlanta',
            'description' => 'Test opportunity',
            'category' => 'unarmed',
            'location' => 'Atlanta, GA',
            'opportunity_status' => $status,
            'scope_version' => '1.0',
        ]);
    }

    private function scope(array $overrides = []): array
    {
        return array_merge([
            'hours_per_day' => 8,
            'days_per_week' => 5,
            'staff_per_shift' => 1,
            'armed_status' => 'unarmed',
            'baseline_wage' => 20.00,
            'service_start_date' => '2026-10-01',
            'duties_required' => ['access_control'],
        ], $overrides);
    }

    public function test_identical_scope_produces_no_version(): void
    {
        $job = $this->job($this->buyer());

        $this->assertNull($this->service->record($job, $this->scope(), $this->scope()));
        $this->assertSame('1.0', $job->fresh()->scope_version);
    }

    /** Reordered arrays and "8" vs 8 must not register as changes. */
    public function test_cosmetic_differences_are_not_changes(): void
    {
        $before = $this->scope(['duties_required' => ['access_control', 'patrol']]);
        $after = $this->scope(['hours_per_day' => '8', 'duties_required' => ['patrol', 'access_control']]);

        $this->assertFalse($this->service->isMaterialChange($before, $after));
    }

    /** After release, a coverage change is material and bumps the major version. */
    public function test_material_change_after_release_bumps_major_version(): void
    {
        $job = $this->job($this->buyer());

        $version = $this->service->record($job, $this->scope(), $this->scope(['hours_per_day' => 24]));

        $this->assertNotNull($version);
        $this->assertTrue($version->is_material);
        $this->assertSame('2.0', $version->version);
        $this->assertSame('2.0', $job->fresh()->scope_version);
    }

    /** Before release there is nothing for a vendor to re-acknowledge: minor bump. */
    public function test_change_before_release_is_minor(): void
    {
        $job = $this->job($this->buyer(), OpportunityStatus::DRAFT);

        $version = $this->service->record($job, $this->scope(), $this->scope(['hours_per_day' => 12]));

        $this->assertNotNull($version);
        $this->assertFalse($version->is_material);
        $this->assertSame('1.1', $version->version);
    }

    /** Every field the spec calls material is actually detected. */
    public function test_each_material_field_is_detected(): void
    {
        $cases = [
            'hours_per_day' => 12,
            'days_per_week' => 7,
            'staff_per_shift' => 3,
            'armed_status' => 'armed',
            'baseline_wage' => 17.00,
            'service_start_date' => '2026-12-01',
            'duties_required' => ['patrol'],
        ];

        foreach ($cases as $field => $newValue) {
            $this->assertTrue(
                $this->service->isMaterialChange($this->scope(), $this->scope([$field => $newValue])),
                "Change to {$field} should be material"
            );
        }
    }

    public function test_change_record_captures_what_moved(): void
    {
        $job = $this->job($this->buyer());

        $version = $this->service->record($job, $this->scope(), $this->scope(['baseline_wage' => 17.00]));

        $changes = $version->changes;
        $this->assertCount(1, $changes);
        $this->assertSame('baseline_wage', $changes[0]['field']);
        $this->assertSame('Baseline wage', $changes[0]['label']);
        $this->assertEquals(20.00, $changes[0]['from']);
        $this->assertEquals(17.00, $changes[0]['to']);
    }

    public function test_version_numbering_sequence(): void
    {
        $this->assertSame('1.1', $this->service->nextVersion('1.0', false));
        $this->assertSame('2.0', $this->service->nextVersion('1.3', true));
        $this->assertSame('3.0', $this->service->nextVersion('2.7', true));
    }

    // ---- Lifecycle (§28) ----

    public function test_lifecycle_helpers(): void
    {
        $this->assertTrue(OpportunityStatus::isLive(OpportunityStatus::OPEN_TO_VENDORS));
        $this->assertFalse(OpportunityStatus::isLive(OpportunityStatus::DRAFT));

        $this->assertTrue(OpportunityStatus::isTerminal(OpportunityStatus::AWARDED));
        $this->assertFalse(OpportunityStatus::isTerminal(OpportunityStatus::OPEN_TO_VENDORS));

        // Pre-release statuses are edits, not versioned changes.
        $this->assertFalse(OpportunityStatus::isReleased(OpportunityStatus::DRAFT));
        $this->assertFalse(OpportunityStatus::isReleased(OpportunityStatus::READY_FOR_RELEASE));
        $this->assertTrue(OpportunityStatus::isReleased(OpportunityStatus::OPEN_TO_VENDORS));

        $this->assertCount(14, OpportunityStatus::all());
    }
}
