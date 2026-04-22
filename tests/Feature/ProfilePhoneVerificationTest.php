<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProfilePhoneVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_update_is_blocked_when_phone_changes_without_verification(): void
    {
        $user = User::factory()->create([
            'phone' => '+14045550100',
            'phone_verified' => true,
        ]);

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Updated Name',
            'email' => $user->email,
            'company' => 'Updated Company',
            'phone' => '+923001112233',
            'city' => 'Karachi',
            'state' => 'Sindh',
            'zip_code' => '75500',
        ]);

        $response->assertSessionHasErrors(['phone']);
        $this->assertSame('+14045550100', $user->fresh()->phone);
    }

    public function test_profile_phone_verification_code_can_be_sent_for_edited_phone_number(): void
    {
        Http::fake([
            'https://api.twilio.com/*' => Http::response(['sid' => 'SM123'], 201),
        ]);

        $user = User::factory()->create([
            'phone' => '+14045550100',
            'phone_verified' => true,
        ]);

        $response = $this->actingAs($user)->post(route('profile.phone.send'), [
            'phone' => '+923001112233',
        ]);

        $response->assertSessionHas('phone_status', 'Verification code sent to your phone.');
        $response->assertSessionHas('profile_phone_verification', function (array $state): bool {
            return $state['phone'] === '+923001112233' && $state['verified'] === false;
        });

        $this->assertDatabaseHas('verification_codes', [
            'user_id' => $user->id,
            'type' => 'sms_profile_update',
            'phone_number' => '+923001112233',
            'status' => 'pending',
        ]);
    }

    public function test_verified_profile_phone_can_be_saved_to_profile(): void
    {
        $user = User::factory()->create([
            'phone' => '+14045550100',
            'phone_verified' => true,
        ]);

        VerificationCode::query()->create([
            'user_id' => $user->id,
            'code' => '',
            'code_hash' => Hash::make('123456'),
            'type' => 'sms_profile_update',
            'phone_number' => '+923001112233',
            'email' => null,
            'status' => 'pending',
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
            'last_sent_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession([
                'profile_phone_verification' => [
                    'phone' => '+923001112233',
                    'verified' => false,
                ],
            ])
            ->post(route('profile.phone.verify'), [
                'phone' => '+923001112233',
                'code' => '123456',
            ])
            ->assertSessionHas('phone_status', 'Phone number verified. You can now save your profile.');

        $updateResponse = $this->actingAs($user)
            ->withSession([
                'profile_phone_verification' => [
                    'phone' => '+923001112233',
                    'verified' => true,
                ],
            ])
            ->put(route('profile.update'), [
                'name' => 'Updated Name',
                'email' => $user->email,
                'company' => 'Updated Company',
                'phone' => '+92 300 1112233',
                'city' => 'Karachi',
                'state' => 'Sindh',
                'zip_code' => '75500',
            ]);

        $updateResponse->assertRedirect(route('profile.show'));
        $updateResponse->assertSessionHas('success', 'Profile updated successfully.');

        $user->refresh();
        $this->assertSame('+923001112233', $user->phone);
        $this->assertTrue($user->phone_verified);
    }
}
