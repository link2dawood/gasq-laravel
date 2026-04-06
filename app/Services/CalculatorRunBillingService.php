<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

class CalculatorRunBillingService
{
    public function __construct(
        private WalletService $walletService
    ) {}

    public function creditsPerRun(): int
    {
        return (int) config('credits.calculator_per_run');
    }

    /**
     * Charge configured credits per run, run the calculator, and roll back the charge if $run throws.
     *
     * @template T
     *
     * @param  callable(): T  $run
     * @return array{0: T, 1: int}
     */
    public function chargeAndRun(User $user, string $featureKey, string $referenceDetail, callable $run): array
    {
        $cost = $this->creditsPerRun();

        return DB::transaction(function () use ($user, $featureKey, $referenceDetail, $run, $cost) {
            $spent = $this->walletService->spendTokens(
                $user,
                $cost,
                $featureKey,
                'Calculator run (' . $cost . ' credits): ' . $referenceDetail,
                null,
            );

            if (! $spent) {
                throw new HttpResponseException(
                    response()->json([
                        'ok' => false,
                        'error' => 'insufficient_credits',
                        'message' => 'Not enough credits. Each calculator run uses ' . $cost . ' credits.',
                        'credits_required' => $cost,
                    ], 402)
                );
            }

            $result = $run();

            return [$result, $this->walletService->getBalance($user)];
        });
    }
}
