<?php

namespace App\Console\Commands;

use App\Services\Trading\MarketDataService;
use Illuminate\Console\Command;

class TradingBacktestCommand extends Command
{
    protected $signature = 'trading:backtest
                            {strategy : Strategy slug to backtest}
                            {symbol : Symbol to test}
                            {--days=30 : Historical days to test}
                            {--starting=10000 : Starting capital}';

    protected $description = 'Backtest a trading strategy on historical data';

    public function handle(MarketDataService $marketData): int
    {
        $strategy = $this->argument('strategy');
        $symbol = $this->argument('symbol');
        $days = $this->option('days');
        $capital = (float) $this->option('starting');

        $this->info("Backtesting {$strategy} on {$symbol} ({$days} days)");
        $this->info("Starting capital: \${$capital}");
        
        // Fetch historical data
        $this->info("Fetching historical data...");
        $candles = $marketData->getHistoricalData($symbol, $days);
        
        if (empty($candles)) {
            $this->error("No historical data available");
            return 1;
        }
        
        $this->info("Loaded " . count($candles) . " candles");
        
        // Run simulation
        $results = $this->simulate($candles, $strategy, $capital);
        
        // Output results
        $this->newLine();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Trades', $results['trades']],
                ['Win Rate', round($results['win_rate'] * 100, 2) . '%'],
                ['Final Equity', '$' . number_format($results['final_equity'], 2)],
                ['Total Return', round($results['return_pct'], 2) . '%'],
                ['Max Drawdown', round($results['max_drawdown'] * 100, 2) . '%'],
            ]
        );
        
        return 0;
    }

    private function simulate(array $candles, string $strategy, float $capital): array
    {
        // Simplified backtest logic - would integrate with TradingStrategyService
        $trades = 0;
        $wins = 0;
        $maxEquity = $capital;
        $currentEquity = $capital;
        
        foreach ($candles as $i => $candle) {
            if ($i < 20) continue; // Skip initial warmup
            
            // Mock strategy signal (50/50 for demo)
            if (rand(0, 100) > 98) {
                $trades++;
                $win = rand(0, 10) > 6; // 40% win rate mock
                
                if ($win) {
                    $wins++;
                    $currentEquity *= 1.02;
                } else {
                    $currentEquity *= 0.98;
                }
                
                if ($currentEquity > $maxEquity) {
                    $maxEquity = $currentEquity;
                }
            }
        }
        
        $returnPct = (($currentEquity - $capital) / $capital) * 100;
        $maxDrawdown = (($maxEquity - min($currentEquity, $maxEquity)) / $maxEquity);
        
        return [
            'trades' => $trades,
            'win_rate' => $trades > 0 ? $wins / $trades : 0,
            'final_equity' => $currentEquity,
            'return_pct' => $returnPct,
            'max_drawdown' => $maxDrawdown,
        ];
    }
}
