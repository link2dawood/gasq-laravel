<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthPhoneVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_phone_verification_page_shows_inline_phone_verification_flow(): void
    {
        $user = User::factory()->create([
            'phone' => '+14045550100',
            'phone_verified' => false,
        ]);

        $response = $this->actingAs($user)->get(route('phone.verify.show'));

        $response->assertOk();
        $response->assertSeeText('Verify your phone');
        $response->assertSeeText('Phone number');
        $response->assertSeeText('Verify');
    }

    public function test_user_can_send_phone_verification_code_for_updated_phone_from_verify_page(): void
    {
        Http::fake([
            'https://api.twilio.com/*' => Http::response(['sid' => 'SM123'], 201),
        ]);

        $user = User::factory()->create([
            'phone' => '+14045550100',
            'phone_verified' => false,
        ]);

        $response = $this->actingAs($user)->post(route('phone.verify.send'), [
            'phone' => '+923001112233',
        ]);

        $response->assertSessionHas('status', 'Verification code sent.');
        $response->assertSessionHas('auth_phone_verification', function (array $state): bool {
            return $state['phone'] === '+923001112233' && $state['verified'] === false;
        });

        $this->assertDatabaseHas('verification_codes', [
            'user_id' => $user->id,
            'type' => 'sms',
            'phone_number' => '+923001112233',
            'status' => 'pending',
        ]);
    }

    public function test_user_can_send_phone_verification_code_when_number_contains_formatting_or_hidden_unicode_marks(): void
    {
        Http::fake([
            'https://api.twilio.com/*' => Http::response(['sid' => 'SM123'], 201),
        ]);

        $user = User::factory()->create([
            'phone' => '+14045550100',
            'phone_verified' => false,
        ]);

        $response = $this->actingAs($user)->post(route('phone.verify.send'), [
            'phone' => "‪+1 (470) 633-2816‬",
        ]);

        $response->assertSessionHas('status', 'Verification code sent.');
        $this->assertDatabaseHas('verification_codes', [
            'user_id' => $user->id,
            'type' => 'sms',
            'phone_number' => '+14706332816',
            'status' => 'pending',
        ]);
    }

    public function test_user_can_verify_updated_phone_from_verify_page(): void
    {
        $user = User::factory()->create([
            'phone' => '+14045550100',
            'phone_verified' => false,
        ]);

        VerificationCode::query()->create([
            'user_id' => $user->id,
            'code' => '',
            'code_hash' => Hash::make('123456'),
            'type' => 'sms',
            'phone_number' => '+923001112233',
            'email' => null,
            'status' => 'pending',
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
            'last_sent_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('phone.verify.check'), [
            'phone' => '+92 300 1112233',
            'code' => '123456',
        ]);

        $response->assertRedirect('/home');

        $user->refresh();
        $this->assertSame('+923001112233', $user->phone);
        $this->assertTrue($user->phone_verified);
    }
}
