<?php

namespace App\Services;

use App\Events\CreditsGranted;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;

class WalletService
{
    public function getOrCreateWallet(User $user): Wallet
    {
        $wallet = $user->wallet;

        if (! $wallet) {
            $wallet = Wallet::create([
                'user_id' => $user->id,
                'balance' => 0,
            ]);
        }

        return $wallet;
    }

    public function getBalance(User $user): int
    {
        $wallet = $this->getOrCreateWallet($user);

        return $wallet->balance;
    }

    public function addTokens(User $user, int $amount, string $type = 'purchase', ?string $description = null, ?string $referenceType = null, ?string $referenceId = null): Wallet
    {
        $wallet = $this->getOrCreateWallet($user);
        $wallet->balance += $amount;
        $wallet->save();

        Transaction::create([
            'user_id' => $user->id,
            'tokens_change' => $amount,
            'type' => $type,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description ?? "Added {$amount} credits",
            'balance_after' => $wallet->balance,
        ]);

        $notifyTypes = ['purchase', 'bonus', 'free_pool', 'grant'];
        if (in_array($type, $notifyTypes, true)) {
            event(new CreditsGranted(
                $user,
                $amount,
                $type,
                $wallet->balance,
                $description ?? "Added {$amount} credits",
            ));
        }

        return $wallet;
    }

    public function spendTokens(User $user, int $amount, string $featureKey, ?string $description = null, ?string $referenceId = null): bool
    {
        $wallet = $this->getOrCreateWallet($user);

        if ($wallet->balance < $amount) {
            return false;
        }

        $wallet->balance -= $amount;
        $wallet->save();

        Transaction::create([
            'user_id' => $user->id,
            'tokens_change' => -$amount,
            'type' => 'spend',
            'reference_type' => $featureKey,
            'reference_id' => $referenceId,
            'description' => $description ?? "Spent {$amount} credits",
            'balance_after' => $wallet->balance,
        ]);

        return true;
    }
}
