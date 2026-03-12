<?php

namespace App\Http\Controllers;

use App\Models\FeatureUsageRule;
use App\Models\User;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminTokensController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index(Request $request): View
    {
        $query = Wallet::with('user')->orderByDesc('balance');

        if ($search = $request->string('q')->toString()) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $wallets = $query->paginate(20)->withQueryString();
        $features = FeatureUsageRule::orderBy('feature_key')->get();

        return view('admin.tokens', [
            'wallets' => $wallets,
            'features' => $features,
            'search' => $search ?? '',
        ]);
    }

    public function adjust(Request $request, WalletService $walletService): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'amount' => ['required', 'integer', 'not_in:0'],
            'grant_type' => ['nullable', 'string', 'in:grant,bonus,free_pool'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var \App\Models\User $user */
        $user = User::findOrFail($data['user_id']);
        $amount = (int) $data['amount'];
        $grantType = $data['grant_type'] ?? 'grant';
        $description = $data['description'] ?? null;

        if ($amount > 0) {
            $defaultDescription = match ($grantType) {
                'bonus' => "Bonus: {$amount} credits",
                'free_pool' => "Free pool allocation: {$amount} credits",
                default => "Admin grant of {$amount} credits",
            };
            $walletService->addTokens(
                $user,
                $amount,
                type: $grantType,
                description: $description ?? $defaultDescription,
                referenceType: 'admin',
                referenceId: null,
            );
        } else {
            $deduct = abs($amount);
            $ok = $walletService->spendTokens(
                $user,
                $deduct,
                featureKey: 'admin',
                description: $description ?? "Admin deduction of {$deduct} credits",
                referenceId: null,
            );

            if (! $ok) {
                return back()->withErrors(['amount' => 'User does not have enough credits for this deduction.'])->withInput();
            }
        }

        return back()->with('success', 'Wallet updated.');
    }

    public function updateFeature(Request $request, FeatureUsageRule $rule): RedirectResponse
    {
        $data = $request->validate([
            'feature_name' => ['required', 'string', 'max:255'],
            'tokens_required' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $rule->update([
            'feature_name' => $data['feature_name'],
            'tokens_required' => $data['tokens_required'],
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Feature rule updated.');
    }
}

