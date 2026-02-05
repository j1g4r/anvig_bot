<?php

namespace App\Console\Commands;

use App\Services\Trading\MarketDataService;
use App\Services\Trading\TradeExecutionService;
use App\Services\Trading\TradingStrategyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TradingRunCommand extends Command
{
    protected $signature = 'trading:run 
                            {--paper : Run in paper trading mode (default)}
                            {--live : Run with real money (if configured)}
                            {--symbols=BTC/USD,ETH/USD : Comma-separated symbols}
                            {--strategies=all : Strategies to run}';

    protected $description = 'Execute trading strategies';

    public function handle(
        TradingStrategyService $strategyService,
        MarketDataService $marketData,
        TradeExecutionService $execution,
    ): int {
        if ($this->option('live')) {
            $this->warn("LIVE TRADING MODE - Real money at stake!");
            if (!$this->confirm("Are you sure?")) {
                return 0;
            }
        }

        $this->info("Starting trading run...");
        
        $symbols = explode(',', $this->option('symbols'));
        $this->info("Markets: " . implode(', ', $symbols));
        
        // Install strategies if none exist
        $strategyService->installDefaults();
        
        // Update positions with current prices
        $this->info("Updating positions...");
        $execution->updatePositions();
        
        // Run all strategies
        $this->info("Running strategy analysis...");
        $results = $strategyService->runAll($symbols);
        
        // Report
        $totalSignals = array_sum(array_map('count', $results));
        $this->info("Generated {$totalSignals} signals");
        
        foreach ($results as $strategy => $trades) {
            $count = count($trades);
            if ($count > 0) {
                $this->info("  {$strategy}: {$count} trades");
            }
        }
        
        // Portfolio snapshot
        $portfolio = $execution->getPortfolioSnapshot();
        $this->newLine();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Cash', '$' . number_format($portfolio['cash'], 2)],
                ['Positions Value', '$' . number_format($portfolio['positions_value'], 2)],
                ['Total Value', '$' . number_format($portfolio['total_value'], 2)],
                ['Realized PnL', '$' . number_format($portfolio['realized_pnl'], 2)],
                ['Unrealized PnL', '$' . number_format($portfolio['unrealized_pnl'], 2)],
                ['Return %', number_format($portfolio['return_pct'], 2) . '%'],
                ['Open Positions', $portfolio['open_positions']],
            ]
        );

        return 0;
    }
}
