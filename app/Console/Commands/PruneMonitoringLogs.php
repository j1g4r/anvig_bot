<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Trace;
use Carbon\Carbon;

class PruneMonitoringLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:prune {days=30}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune monitoring traces older than a specified number of days (default: 30)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->argument('days');
        $cutoff = Carbon::now()->subDays($days);

        $count = Trace::where('created_at', '<', $cutoff)->delete();

        $this->info("Pruned {$count} monitoring traces older than {$days} days.");
        return 0;
    }
}
