<?php

namespace App\Console\Commands;

use App\Services\VendorOpportunityManager;
use Illuminate\Console\Command;

class ProcessVendorOpportunityAutomationCommand extends Command
{
    protected $signature = 'vendor-opportunities:process';

    protected $description = 'Process vendor opportunity reminders, final notices, and bid-window expirations';

    public function handle(VendorOpportunityManager $manager): int
    {
        $manager->processAutomation();

        $this->info('Vendor opportunity automation processed.');

        return self::SUCCESS;
    }
}
