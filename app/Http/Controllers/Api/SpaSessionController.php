<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeatureUsageRule;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpaSessionController extends Controller
{
    public function __construct(
        private WalletService $walletService
    ) {}

    /**
     * Single JSON bootstrap for the embedded React SPA: CSRF, auth user, wallet, feature rules.
     */
    public function show(Request $request): JsonResponse
    {
        $featureRules = FeatureUsageRule::query()
            ->where('is_active', true)
            ->get()
            ->mapWithKeys(fn (FeatureUsageRule $r) => [$r->feature_key => [
                'feature_key' => $r->feature_key,
                'feature_name' => $r->feature_name,
                'tokens_required' => $r->tokens_required,
                'description' => $r->description ?? '',
            ]]);

        $user = $request->user();

        return response()->json([
            'csrf' => csrf_token(),
            'authenticated' => $user !== null,
            'user' => $user === null ? null : [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->isAdmin(),
                'avatar_url' => $user->avatar_url,
                'wallet_balance' => $this->walletService->getBalance($user),
            ],
            'feature_rules' => $featureRules,
        ]);
    }
}
