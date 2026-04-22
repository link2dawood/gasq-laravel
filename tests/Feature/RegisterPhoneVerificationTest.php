<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegisterPhoneVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_surfaces_twilio_send_failures_to_user(): void
    {
        Http::fake([
            'https://api.twilio.com/*' => Http::response(['message' => 'Twilio error'], 500),
        ]);

        Notification::fake();

        $response = $this->post(route('register'), [
            'name' => 'Dawood Zafar',
            'email' => 'dawood@example.com',
            'user_type' => 'buyer',
            'company' => 'Reptiles',
            'phone' => '+923063301114',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $response->assertRedirect(route('phone.verify.show'));
        $response->assertSessionHasErrors([
            'phone' => 'We could not send a verification code right now. Please try again in a moment.',
        ]);
    }
}
