<?php

namespace Tests\Feature;

use App\Mail\PrivateEstimateInvitationMail;
use App\Mail\PrivateEstimateOtpMail;
use App\Models\EstimateInvitation;
use App\Models\PrivateEstimate;
use App\Models\User;
use App\Models\VerificationCode;
use App\Services\PrivateEstimate\BuyerEmailOtpService;
use App\Services\PrivateEstimate\EstimateInvitationService;
use App\Services\PrivateEstimate\PrivateEstimateService;
use App\Support\PrivateEstimate as Flow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The invitation half of the GASQ Private Estimate spec: secure tokens (2),
 * expiry and revocation (3), the buyer invitation page (4) and email OTP (5).
 */
class PrivateEstimateInvitationTest extends TestCase
{
    use RefreshDatabase;

    private function vendor(): User
    {
        return User::factory()->create(['user_type' => 'vendor', 'company' => 'ABC Security']);
    }

    private function estimate(?User $vendor = null): PrivateEstimate
    {
        return app(PrivateEstimateService::class)->create($vendor ?: $this->vendor(), [
            'company_name' => 'ABC Properties',
            'buyer_name' => 'Jane Smith',
            'buyer_email' => 'jane@abcproperties.test',
            'site_name' => 'Main Entrance',
        ]);
    }

    public function test_public_id_is_sequential_per_day_and_hides_the_database_id(): void
    {
        $vendor = $this->vendor();
        $first = $this->estimate($vendor);
        $second = $this->estimate($vendor);

        $this->assertMatchesRegularExpression('/^PE-\d{6}-\d{4}$/', $first->public_id);
        $this->assertSame('PE-'.now()->format('ymd').'-0001', $first->public_id);
        $this->assertSame('PE-'.now()->format('ymd').'-0002', $second->public_id);
        // The sequence counts estimates created that day, not database ids.
        $this->assertNotSame((string) $first->id, $first->public_id);
        $this->assertSame(Flow::INVITATION_CREATED, $first->status);
    }

    public function test_only_the_token_hash_is_stored(): void
    {
        $estimate = $this->estimate();
        ['token' => $token, 'invitation' => $invitation] = app(EstimateInvitationService::class)->issue($estimate, 7);

        $this->assertSame(hash('sha256', $token), $invitation->token_hash);
        $this->assertDatabaseMissing('estimate_invitations', ['token_hash' => $token]);
        $this->assertTrue($invitation->expires_at->isFuture());
    }

    public function test_issuing_a_new_invitation_revokes_the_previous_link(): void
    {
        $estimate = $this->estimate();
        $service = app(EstimateInvitationService::class);

        ['token' => $first] = $service->issue($estimate, 7);
        ['token' => $second] = $service->issue($estimate, 7);

        $this->assertSame('revoked', $service->resolve($first)['reason']);
        $this->assertNotNull($service->resolve($second)['invitation']);
    }

    public function test_expired_and_revoked_links_show_the_spec_messages(): void
    {
        $service = app(EstimateInvitationService::class);

        $expiredEstimate = $this->estimate();
        ['token' => $expiredToken, 'invitation' => $expired] = $service->issue($expiredEstimate, 3);
        $expired->forceFill(['expires_at' => now()->subDay()])->save();

        $this->get(route('private-estimates.buyer.show', $expiredToken))
            ->assertOk()
            ->assertSee('This Private Estimate Invitation Has Expired')
            ->assertSee('Request New Invitation');

        $revokedEstimate = $this->estimate();
        ['token' => $revokedToken, 'invitation' => $invitation] = $service->issue($revokedEstimate, 3);
        $service->revoke($invitation, 'Vendor withdrew');

        $this->get(route('private-estimates.buyer.show', $revokedToken))
            ->assertOk()
            ->assertSee('This Private Estimate Invitation Was Withdrawn');

        $this->get(route('private-estimates.buyer.show', 'not-a-real-token'))
            ->assertOk()
            ->assertSee('Is Not Available');
    }

    public function test_sending_an_invitation_emails_the_buyer_and_records_it(): void
    {
        Mail::fake();
        $estimate = $this->estimate();

        $invitation = app(EstimateInvitationService::class)->issueAndSend($estimate, 15);

        Mail::assertSent(PrivateEstimateInvitationMail::class, fn ($mail) => $mail->hasTo('jane@abcproperties.test'));
        $this->assertNotNull($invitation->sent_at);
        $this->assertSame(Flow::INVITATION_SENT, $estimate->fresh()->status);
        $this->assertDatabaseHas('estimate_activity', [
            'private_estimate_id' => $estimate->id,
            'event_type' => Flow::EVENT_INVITATION_SENT,
        ]);
    }

    public function test_the_link_alone_never_shows_the_estimate(): void
    {
        Mail::fake();
        $estimate = $this->estimate();
        ['token' => $token] = app(EstimateInvitationService::class)->issue($estimate, 7);

        $response = $this->get(route('private-estimates.buyer.show', $token));

        $response->assertOk()
            ->assertSee("You've Been Invited to Receive a Private Security Service Estimate", false)
            ->assertSee('Review Private Invitation')
            ->assertDontSee("You're verified", false);

        $this->assertNotNull($estimate->fresh()->invitation->opened_at);
        $this->assertSame(Flow::INVITATION_OPENED, $estimate->fresh()->status);
    }

    public function test_buyer_verifies_with_an_emailed_code_and_reaches_the_portal(): void
    {
        Mail::fake();
        $estimate = $this->estimate();
        ['token' => $token] = app(EstimateInvitationService::class)->issue($estimate, 7);
        $this->get(route('private-estimates.buyer.show', $token));

        $this->post(route('private-estimates.buyer.send-code', $token))->assertRedirect();
        Mail::assertSent(PrivateEstimateOtpMail::class, fn ($mail) => $mail->hasTo('jane@abcproperties.test'));

        // The code is hashed at rest, so read it from the mailable the buyer got.
        $code = null;
        Mail::assertSent(PrivateEstimateOtpMail::class, function ($mail) use (&$code) {
            $code = $mail->code;

            return true;
        });
        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $code);
        $this->assertDatabaseMissing('verification_codes', ['code_hash' => $code]);

        $this->post(route('private-estimates.buyer.verify-code', $token), ['code' => $code])
            ->assertRedirect(route('private-estimates.buyer.show', $token));

        $estimate->refresh();
        $this->assertNotNull($estimate->buyer_email_verified_at);
        $this->assertSame(Flow::BUYER_VERIFIED, $estimate->status);
        $this->assertDatabaseHas('estimate_activity', [
            'private_estimate_id' => $estimate->id,
            'event_type' => Flow::EVENT_EMAIL_VERIFIED,
        ]);

        $this->get(route('private-estimates.buyer.show', $token))->assertOk()->assertSee("You're verified", false);
    }

    public function test_a_wrong_code_is_rejected_and_attempts_are_capped(): void
    {
        Mail::fake();
        $estimate = $this->estimate();
        ['token' => $token] = app(EstimateInvitationService::class)->issue($estimate, 7);
        app(BuyerEmailOtpService::class)->send($estimate);

        for ($i = 0; $i < BuyerEmailOtpService::MAX_ATTEMPTS; $i++) {
            $this->post(route('private-estimates.buyer.verify-code', $token), ['code' => '000000'])
                ->assertSessionHasErrors('code');
        }

        $this->assertNull($estimate->fresh()->buyer_email_verified_at);
        $this->post(route('private-estimates.buyer.verify-code', $token), ['code' => '000000'])
            ->assertSessionHasErrors('code');
        $this->assertSame('failed', VerificationCode::query()->latest('id')->first()->status);
    }

    public function test_an_expired_code_is_refused(): void
    {
        Mail::fake();
        $estimate = $this->estimate();
        $code = null;
        app(BuyerEmailOtpService::class)->send($estimate);
        Mail::assertSent(PrivateEstimateOtpMail::class, function ($mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        VerificationCode::query()->latest('id')->first()
            ->forceFill(['expires_at' => now()->subMinute()])->save();

        $result = app(BuyerEmailOtpService::class)->verify($estimate, (string) $code);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('expired', $result['message']);
    }

    public function test_a_code_issued_for_one_estimate_cannot_open_another(): void
    {
        Mail::fake();
        $vendor = $this->vendor();
        $mine = $this->estimate($vendor);
        $theirs = $this->estimate($vendor);

        $code = null;
        app(BuyerEmailOtpService::class)->send($mine);
        Mail::assertSent(PrivateEstimateOtpMail::class, function ($mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        $this->assertFalse(app(BuyerEmailOtpService::class)->verify($theirs, (string) $code)['ok']);
        $this->assertTrue(app(BuyerEmailOtpService::class)->verify($mine, (string) $code)['ok']);
    }

    public function test_resending_is_rate_limited_by_a_cooldown(): void
    {
        Mail::fake();
        $estimate = $this->estimate();
        $otp = app(BuyerEmailOtpService::class);

        $this->assertTrue($otp->send($estimate)['ok']);
        $second = $otp->send($estimate);

        $this->assertFalse($second['ok']);
        $this->assertStringContainsString('wait', $second['message']);
        Mail::assertSentCount(1);
    }

    public function test_status_transitions_are_controlled(): void
    {
        $estimate = $this->estimate();
        $service = app(PrivateEstimateService::class);

        // Pricing cannot be reached before a vendor accepts a confirmed scope.
        $this->assertFalse($service->can($estimate, Flow::ESTIMATE_READY));
        $this->expectException(\RuntimeException::class);
        $service->transition($estimate, Flow::ESTIMATE_READY);
    }

    public function test_overdue_estimates_expire_and_keep_their_history(): void
    {
        Mail::fake();
        $estimate = $this->estimate();
        app(EstimateInvitationService::class)->issueAndSend($estimate, 3);
        $estimate->forceFill(['valid_until' => now()->subDay()])->save();

        $this->assertSame(1, app(PrivateEstimateService::class)->expireOverdue());
        $this->assertSame(Flow::EXPIRED, $estimate->fresh()->status);
        $this->assertDatabaseHas('estimate_activity', [
            'private_estimate_id' => $estimate->id,
            'event_type' => Flow::EVENT_INVITATION_SENT,
        ]);
    }

    public function test_invitations_belong_to_their_estimate(): void
    {
        $estimate = $this->estimate();
        ['invitation' => $invitation] = app(EstimateInvitationService::class)->issue($estimate, 7);

        $this->assertInstanceOf(EstimateInvitation::class, $estimate->fresh()->invitation);
        $this->assertSame($estimate->id, $invitation->privateEstimate->id);
        $this->assertSame('jane@abcproperties.test', $invitation->invited_email);
    }
}
