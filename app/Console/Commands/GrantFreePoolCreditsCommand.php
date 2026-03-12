<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\WalletService;
use Illuminate\Console\Command;

class GrantFreePoolCreditsCommand extends Command
{
    protected $signature = 'credits:grant-free-pool
                            {--amount=5 : Credits to grant per user}
                            {--max-balance= : Only grant to users with balance below this (optional)}
                            {--dry-run : List users that would receive credits without granting}';

    protected $description = 'Grant bonus/free-pool credits to users and send notification emails';

    public function handle(WalletService $walletService): int
    {
        $amount = (int) $this->option('amount');
        $maxBalance = $this->option('max-balance') !== null ? (int) $this->option('max-balance') : null;
        $dryRun = $this->option('dry-run');

        if ($amount <= 0) {
            $this->error('Amount must be positive.');
            return self::FAILURE;
        }

        $query = User::query();
        if ($maxBalance !== null) {
            $query->where(function ($q) use ($maxBalance) {
                $q->whereDoesntHave('wallet')
                    ->orWhereHas('wallet', fn ($w) => $w->where('balance', '<', $maxBalance));
            });
        }
        $users = $query->with('wallet')->get();

        if ($users->isEmpty()) {
            $this->warn('No users match the criteria.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info("Dry run: would grant {$amount} credits to {$users->count()} user(s).");
            $this->table(
                ['ID', 'Name', 'Email', 'Current balance'],
                $users->map(fn (User $u) => [$u->id, $u->name, $u->email, $u->wallet?->balance ?? 0])
            );
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($users->count());
        $bar->start();
        foreach ($users as $user) {
            $walletService->addTokens(
                $user,
                $amount,
                type: 'free_pool',
                description: 'Monthly free pool allocation',
                referenceType: 'schedule',
                referenceId: 'credits:grant-free-pool',
            );
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
        $this->info("Granted {$amount} credits to {$users->count()} user(s). Emails sent.");

        return self::SUCCESS;
    }
}
