<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Str;
use Tests\TestCase;

class VendorWorkflowSecurityTest extends TestCase
{
    public function test_public_password_and_booking_actions_require_csrf_outside_test_environment(): void
    {
        $this->withMiddleware(ValidateCsrfToken::class);
        $this->app->detectEnvironment(fn () => 'production');
        $token = Str::random(64);
        foreach (['vendor-pdf.code', 'vendor-pdf.verify', 'vendor-pdf.reveal', 'vendor-pdf.download', 'vendor-review.book', 'vendor-review.cancel'] as $route) {
            $this->postJson(route($route, $token), [])->assertStatus(419);
        }
    }
}
