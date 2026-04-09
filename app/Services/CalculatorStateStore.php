<?php

namespace App\Services;

use App\Models\CalculatorState;
use App\Models\User;

class CalculatorStateStore
{
    /**
     * Persist the latest calculator state for a user and calculator type.
     *
     * @param  array<string, mixed>  $scenario
     * @param  array<string, mixed>  $result
     */
    public function store(?User $user, string $type, array $scenario = [], array $result = []): void
    {
        if (! $user) {
            return;
        }

        CalculatorState::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'calculator_type' => $type,
            ],
            [
                'scenario' => $scenario,
                'result' => $result,
                'last_ran_at' => now(),
            ],
        );
    }
}
