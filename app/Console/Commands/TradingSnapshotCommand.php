<?php

namespace App\Console\Commands;

use App\Models\PortfolioSnapshot;
use App\Services\Trading\TradeExecutionService;
use Illuminate\Console\Command;

class TradingSnapshotCommand extends Command
{
    protected $signature = 'trading:snapshot {--paper : Snapshot paper portfolio}';

    protected $description = 'Capture portfolio snapshot for performance tracking';

    public function handle(TradeExecutionService $execution): int
    {
        $paper = $this->option('paper');
        
        $snapshot = PortfolioSnapshot::capture($execution->getPortfolioSnapshot($paper));
        
        $this->info(sprintf(
            'Portfolio snapshot captured at %s: %s %.2f',
            $snapshot->snapshot_at->format('c'),
            $paper ? 'PAPER' : 'LIVE',
            $snapshot->total_balance
        ));
        
        return Command::SUCCESS;
    }
}
