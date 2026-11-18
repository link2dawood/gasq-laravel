<?php

namespace App\Console\Commands;

use App\Jobs\SendVendorEstimateDelivery;
use App\Models\VendorEstimateDelivery;
use Illuminate\Console\Command;

/**
 * Safety net for estimate emails that the queue never delivered.
 *
 * Every delivery dispatches its send job the moment it is created, so a row
 * still `queued` minutes later means no worker claimed it. Left alone it stays
 * queued forever: the buyer never receives the estimate and the buyer access
 * page 404s, because that page only answers for a delivery that was sent.
 *
 * This runs those sends in-process from the scheduler, so a host with a
 * minutely cron but no long-running worker still delivers. The job claims each
 * row with a conditional update, so a worker that starts later cannot send a
 * second copy of the same estimate.
 */
class FlushVendorEstimateDeliveries extends Command
{
    protected $signature = 'vendor-estimates:flush-deliveries {--minutes=2 : Only flush deliveries queued at least this long}';

    protected $description = 'Send vendor estimate emails left queued because no queue worker claimed them';

    public function handle(): int
    {
        $minutes = max(0, (int) $this->option('minutes'));
        $stale = VendorEstimateDelivery::where('status', 'queued')
            ->where('created_at', '<=', now()->subMinutes($minutes))
            ->orderBy('id')
            ->pluck('id');

        foreach ($stale as $id) {
            try {
                SendVendorEstimateDelivery::dispatchSync($id);
            } catch (\Throwable $exception) {
                // The job records its own failure state; keep going so one bad
                // delivery cannot block the rest of the backlog.
                $this->warn('Delivery '.$id.' could not be sent: '.class_basename($exception));
            }
        }

        $this->info('Queued estimate deliveries flushed: '.$stale->count().'.');

        return self::SUCCESS;
    }
}
