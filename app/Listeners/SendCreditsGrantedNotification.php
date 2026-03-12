<?php

namespace App\Listeners;

use App\Events\CreditsGranted;
use App\Mail\TokenNotificationMail;
use Illuminate\Support\Facades\Mail;

class SendCreditsGrantedNotification
{
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
}
