<?php

namespace App\Console\Commands;

use App\Services\ExchangeRateService;
use Illuminate\Console\Command;

class RefreshExchangeRates extends Command
{
    protected $signature = 'currency:refresh';

    protected $description = 'Refresh all supported USD cross rates from the latest ECB daily reference sheet';

    public function handle(ExchangeRateService $rates): int
    {
        try {
            $sheet = $rates->refresh();
        } catch (\Throwable) {
            $this->error('The ECB sheet could not be verified. Previous reference rates were retained.');

            return self::FAILURE;
        }
        $this->info('ECB reference rates refreshed: '.$sheet['asOf'].'; base USD; '.count($sheet['rates']).' currencies.');

        return self::SUCCESS;
    }
}
