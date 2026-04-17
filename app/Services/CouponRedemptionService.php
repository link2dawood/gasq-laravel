<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CouponRedemptionService
{
    public function __construct(
        private WalletService $walletService
    ) {}

    public function normalizeCode(string $code): string
    {
        return strtoupper(trim($code));
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     */
    public function redeem(User $user, string $submittedCode): Coupon
    {
        $code = $this->normalizeCode($submittedCode);

        return DB::transaction(function () use ($user, $code) {
            $coupon = Coupon::query()
                ->where('code', $code)
                ->lockForUpdate()
                ->first();

            if (! $coupon) {
                throw ValidationException::withMessages([
                    'code' => 'That coupon code is invalid.',
                ]);
            }

            if (! $coupon->is_active) {
                throw ValidationException::withMessages([
                    'code' => 'That coupon code is not active.',
                ]);
            }

            if ($coupon->expires_at && $coupon->expires_at->isPast()) {
                throw ValidationException::withMessages([
                    'code' => 'That coupon code has expired.',
                ]);
            }

            $alreadyRedeemed = CouponRedemption::query()
                ->where('coupon_id', $coupon->id)
                ->where('user_id', $user->id)
                ->exists();

            if ($alreadyRedeemed) {
                throw ValidationException::withMessages([
                    'code' => 'You have already redeemed this coupon code.',
                ]);
            }

            if ($coupon->max_redemptions !== null) {
                $redemptionCount = CouponRedemption::query()
                    ->where('coupon_id', $coupon->id)
                    ->count();

                if ($redemptionCount >= $coupon->max_redemptions) {
                    throw ValidationException::withMessages([
                        'code' => 'That coupon code has reached its redemption limit.',
                    ]);
                }
            }

            $description = "Coupon {$coupon->code} redeemed for {$coupon->credits_amount} credits";

            $this->walletService->addTokens(
                $user,
                $coupon->credits_amount,
                type: 'coupon',
                description: $description,
                referenceType: 'coupon',
                referenceId: (string) $coupon->id,
            );

            $coupon->redemptions()->create([
                'user_id' => $user->id,
                'credits_amount' => $coupon->credits_amount,
            ]);

            return $coupon;
        });
    }
}
