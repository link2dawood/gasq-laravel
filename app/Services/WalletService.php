<?php

namespace App\Services;

use App\Events\CreditsGranted;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function getOrCreateWallet(User $user): Wallet
    {
        $wallet = Wallet::query()->where('user_id', $user->id)->first();
        if ($wallet) {
            return $wallet;
        }

        return $this->createWallet($user);
    }

    public function getBalance(User $user): int
    {
        $wallet = $this->getOrCreateWallet($user);

        return $wallet->balance;
    }

    public function addTokens(
        User $user,
        int $amount,
        string $type = 'purchase',
        ?string $description = null,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $idempotencyKey = null,
    ): Wallet {
        $wallet = DB::transaction(function () use (
            $user,
            $amount,
            $type,
            $description,
            $referenceType,
            $referenceId,
            $idempotencyKey
        ) {
            $wallet = $this->lockWallet($user);
            $wallet->balance += $amount;
            $wallet->save();

            Transaction::create([
                'user_id' => $user->id,
                'tokens_change' => $amount,
                'type' => $type,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'idempotency_key' => $idempotencyKey,
                'description' => $description ?? "Added {$amount} credits",
                'balance_after' => $wallet->balance,
            ]);

            return $wallet->fresh();
        });

        $notifyTypes = ['purchase', 'bonus', 'free_pool', 'grant', 'coupon'];
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
        return DB::transaction(function () use ($user, $amount, $featureKey, $description, $referenceId) {
            $wallet = $this->lockWallet($user);

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
                'idempotency_key' => null,
                'description' => $description ?? "Spent {$amount} credits",
                'balance_after' => $wallet->balance,
            ]);

            return true;
        });
    }

    private function lockWallet(User $user): Wallet
    {
        $wallet = Wallet::query()
            ->where('user_id', $user->id)
            ->lockForUpdate()
            ->first();

        if ($wallet) {
            return $wallet;
        }

        $created = $this->createWallet($user);

        return Wallet::query()
            ->whereKey($created->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function createWallet(User $user): Wallet
    {
        try {
            return Wallet::query()->create([
                'user_id' => $user->id,
                'balance' => 0,
            ]);
        } catch (QueryException $e) {
            if (! $this->isDuplicateKey($e)) {
                throw $e;
            }

            return Wallet::query()->where('user_id', $user->id)->firstOrFail();
        }
    }

    private function isDuplicateKey(QueryException $e): bool
    {
        $sqlState = $e->errorInfo[0] ?? null;
        $driverCode = $e->errorInfo[1] ?? null;

        return $sqlState === '23000' || $driverCode === 1062 || $driverCode === 19;
    }
}
