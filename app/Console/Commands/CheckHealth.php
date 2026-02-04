<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agent:check_health';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform a system health check (Dummy Implementation)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("System health check passed.");
        Log::info("agent:check_health executed successfully.");
        return 0;
    }
}
