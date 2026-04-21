<?php

namespace App\Listeners;

use App\Events\CreditsGranted;
use App\Mail\TokenNotificationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendCreditsGrantedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public bool $afterCommit = true;

    public function handle(CreditsGranted $event): void
    {
        $type = $event->type;
        $emailType = $type === 'free_pool' ? 'free_pool_refresh' : $type;

        Mail::to($event->user->email)->send(new TokenNotificationMail(
            type: $emailType,
            userName: $event->user->name ?? 'there',
            tokensChange: $event->amount,
            currentBalance: $event->balanceAfter,
            reason: $event->description,
        ));
    }

    public function failed(CreditsGranted $event, Throwable $e): void
    {
        Log::error('Credits granted notification failed.', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'type' => $event->type,
            'amount' => $event->amount,
            'error' => $e->getMessage(),
        ]);
    }
}
