<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeatureUsageRule;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpaWalletController extends Controller
{
    public function __construct(
        private WalletService $walletService
    ) {}

    public function spend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'feature_key' => ['required', 'string'],
            'reference_id' => ['nullable', 'string'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $rule = FeatureUsageRule::query()
            ->where('feature_key', $validated['feature_key'])
            ->where('is_active', true)
            ->first();

        if (! $rule) {
            return response()->json([
                'success' => false,
                'error' => 'unknown_feature',
            ]);
        }

        $amount = (int) $rule->tokens_required;
        $wallet = $this->walletService->getOrCreateWallet($user);

        if ($wallet->balance < $amount) {
            return response()->json([
                'success' => false,
                'error' => 'insufficient_balance',
                'required' => $amount,
                'current_balance' => $wallet->balance,
            ]);
        }

        $this->walletService->spendTokens(
            $user,
            $amount,
            $validated['feature_key'],
            $validated['description'] ?? null,
            $validated['reference_id'] ?? null
        );

        return response()->json([
            'success' => true,
            'tokens_spent' => $amount,
            'feature_name' => $rule->feature_name,
            'balance_after' => $this->walletService->getBalance($user),
        ]);
    }
}
