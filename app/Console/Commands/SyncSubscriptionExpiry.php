<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncSubscriptionExpiry extends Command
{
    protected $signature = 'subscriptions:sync-expiry';
    protected $description = 'Compatibility command for scheduled subscription expiry checks';

    public function handle(): int
    {
        $this->call('subscriptions:expire');
        return self::SUCCESS;
    }
}
