<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SelfHealingService;

class AutoHealQueue extends Command
{
    protected $signature = 'queue:auto-heal';
    protected $description = 'Analyze failed jobs and automatically retry transient errors or dispatch bug reports.';

    public function handle(SelfHealingService $healer)
    {
        $this->info('Starting Queue Self-Healing...');
        $healer->heal();
        $this->info('Healing Cycle Complete.');
    }
}
