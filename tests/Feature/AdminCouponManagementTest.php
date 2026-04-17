<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCouponManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_delete_coupon_codes(): void
    {
        $admin = $this->makeAdmin();

        $createResponse = $this->actingAs($admin)->post(route('admin.coupons.store'), [
            'code' => ' spring50 ',
            'credits_amount' => 50,
            'max_redemptions' => 25,
            'expires_at' => now()->addWeek()->format('Y-m-d H:i:s'),
            'is_active' => '1',
        ]);

        $createResponse->assertRedirect(route('admin.coupons.index'));
        $this->assertDatabaseHas('coupons', [
            'code' => 'SPRING50',
            'credits_amount' => 50,
            'max_redemptions' => 25,
            'is_active' => true,
        ]);

        $coupon = Coupon::query()->where('code', 'SPRING50')->firstOrFail();

        $updateResponse = $this->actingAs($admin)->put(route('admin.coupons.update', $coupon), [
            'code' => 'spring60',
            'credits_amount' => 60,
            'max_redemptions' => '',
            'expires_at' => '',
        ]);

        $updateResponse->assertRedirect(route('admin.coupons.index'));
        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'code' => 'SPRING60',
            'credits_amount' => 60,
            'max_redemptions' => null,
            'is_active' => false,
        ]);

        $deleteResponse = $this->actingAs($admin)->delete(route('admin.coupons.destroy', $coupon));

        $deleteResponse->assertRedirect(route('admin.coupons.index'));
        $this->assertDatabaseMissing('coupons', [
            'id' => $coupon->id,
        ]);
    }

    public function test_admin_coupon_validation_rejects_invalid_payload(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->from(route('admin.coupons.create'))
            ->actingAs($admin)
            ->post(route('admin.coupons.store'), [
                'code' => '',
                'credits_amount' => 0,
                'max_redemptions' => 0,
            ]);

        $response->assertRedirect(route('admin.coupons.create'));
        $response->assertSessionHasErrors(['code', 'credits_amount', 'max_redemptions']);
        $this->assertDatabaseCount('coupons', 0);
    }

    private function makeAdmin(): User
    {
        return User::factory()->create([
            'user_type' => 'admin',
            'phone' => null,
            'phone_verified' => true,
        ]);
    }
}
