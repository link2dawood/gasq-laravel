<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CreditsGranted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,
        public int $amount,
        public string $type,
        public int $balanceAfter,
        public ?string $description = null,
    ) {}
}
