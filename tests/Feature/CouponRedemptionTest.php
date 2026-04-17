<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\JobPosting;
use App\Models\PricingPlan;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponRedemptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_credits_page_renders_coupon_form_and_purchase_plans(): void
    {
        $user = $this->makeUser('vendor');

        PricingPlan::create([
            'name' => 'Starter',
            'price' => 29.00,
            'tokens_included' => 25,
            'features' => ['Calculators'],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($user)->get(route('credits'));

        $response->assertOk();
        $response->assertSee('Redeem coupon');
        $response->assertSee('Purchase with Stripe');
    }

    public function test_valid_coupon_adds_credits_and_records_redemption_and_transaction(): void
    {
        $user = $this->makeUser('vendor');
        $coupon = Coupon::create([
            'code' => 'SPRING25',
            'credits_amount' => 12,
            'is_active' => true,
        ]);

        $response = $this->from(route('credits'))
            ->actingAs($user)
            ->post(route('credits.redeem'), ['code' => ' spring25 ']);

        $response->assertRedirect(route('credits'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'balance' => 12,
        ]);

        $this->assertDatabaseHas('coupon_redemptions', [
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'credits_amount' => 12,
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => 'coupon',
            'reference_type' => 'coupon',
            'reference_id' => (string) $coupon->id,
            'tokens_change' => 12,
        ]);
    }

    public function test_buyer_without_job_is_redirected_to_job_creation_after_coupon_redemption(): void
    {
        $user = $this->makeUser('buyer');

        Coupon::create([
            'code' => 'BUYER10',
            'credits_amount' => 10,
            'is_active' => true,
        ]);

        $response = $this->from(route('credits'))
            ->actingAs($user)
            ->post(route('credits.redeem'), ['code' => 'BUYER10']);

        $response->assertRedirect(route('jobs.create'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'balance' => 10,
        ]);
    }

    public function test_invalid_coupon_is_rejected_without_wallet_changes(): void
    {
        $user = $this->makeUser('vendor');

        $response = $this->from(route('credits'))
            ->actingAs($user)
            ->post(route('credits.redeem'), ['code' => 'NOPE']);

        $response->assertRedirect(route('credits'));
        $response->assertSessionHasErrors('code');

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'balance' => 0,
        ]);

        $this->assertDatabaseCount('coupon_redemptions', 0);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_inactive_coupon_is_rejected(): void
    {
        $user = $this->makeUser('vendor');

        Coupon::create([
            'code' => 'OFFLINE',
            'credits_amount' => 8,
            'is_active' => false,
        ]);

        $response = $this->from(route('credits'))
            ->actingAs($user)
            ->post(route('credits.redeem'), ['code' => 'OFFLINE']);

        $response->assertRedirect(route('credits'));
        $response->assertSessionHasErrors('code');
        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'balance' => 0,
        ]);
    }

    public function test_expired_coupon_is_rejected(): void
    {
        $user = $this->makeUser('vendor');

        Coupon::create([
            'code' => 'OLDIE',
            'credits_amount' => 8,
            'expires_at' => now()->subMinute(),
            'is_active' => true,
        ]);

        $response = $this->from(route('credits'))
            ->actingAs($user)
            ->post(route('credits.redeem'), ['code' => 'OLDIE']);

        $response->assertRedirect(route('credits'));
        $response->assertSessionHasErrors('code');
        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'balance' => 0,
        ]);
    }

    public function test_same_user_cannot_redeem_the_same_coupon_twice(): void
    {
        $user = $this->makeUser('vendor');

        Coupon::create([
            'code' => 'ONETIMEPERUSER',
            'credits_amount' => 7,
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('credits.redeem'), ['code' => 'ONETIMEPERUSER']);

        $response = $this->from(route('credits'))
            ->actingAs($user)
            ->post(route('credits.redeem'), ['code' => 'ONETIMEPERUSER']);

        $response->assertRedirect(route('credits'));
        $response->assertSessionHasErrors('code');

        $this->assertDatabaseCount('coupon_redemptions', 1);
        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'balance' => 7,
        ]);
    }

    public function test_coupon_with_max_redemptions_is_rejected_after_limit_is_reached(): void
    {
        $firstUser = $this->makeUser('vendor');
        $secondUser = $this->makeUser('vendor');

        Coupon::create([
            'code' => 'LIMIT1',
            'credits_amount' => 5,
            'max_redemptions' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($firstUser)->post(route('credits.redeem'), ['code' => 'LIMIT1']);

        $response = $this->from(route('credits'))
            ->actingAs($secondUser)
            ->post(route('credits.redeem'), ['code' => 'LIMIT1']);

        $response->assertRedirect(route('credits'));
        $response->assertSessionHasErrors('code');

        $this->assertDatabaseCount('coupon_redemptions', 1);
        $this->assertDatabaseHas('wallets', [
            'user_id' => $secondUser->id,
            'balance' => 0,
        ]);
    }

    public function test_coupon_credits_do_not_bypass_buyer_job_requirement_for_calculator_access(): void
    {
        $user = $this->makeUser('buyer');

        Coupon::create([
            'code' => 'ACCESS10',
            'credits_amount' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('credits.redeem'), ['code' => 'ACCESS10']);

        $response = $this->actingAs($user)->get(route('main-menu-calculator.index'));

        $response->assertRedirect(route('jobs.create'));
    }

    public function test_buyer_with_existing_job_returns_to_credits_after_successful_redemption(): void
    {
        $user = $this->makeUser('buyer');

        JobPosting::create([
            'user_id' => $user->id,
            'title' => 'Existing job',
            'guards_per_shift' => 1,
        ]);

        Coupon::create([
            'code' => 'HASJOB',
            'credits_amount' => 9,
            'is_active' => true,
        ]);

        $response = $this->from(route('credits'))
            ->actingAs($user)
            ->post(route('credits.redeem'), ['code' => 'HASJOB']);

        $response->assertRedirect(route('credits'));
        $response->assertSessionHas('success');
    }

    private function makeUser(string $type): User
    {
        $user = User::factory()->create([
            'user_type' => $type,
            'phone' => null,
            'phone_verified' => true,
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'balance' => 0,
        ]);

        return $user;
    }
}
