<?php

namespace App\Services\Trading;

use App\Models\Trade;
use App\Models\PortfolioSnapshot;
use Illuminate\Support\Facades\DB;

class PortfolioAnalyticsService
{
    /**
     * Calculate comprehensive performance metrics
     */
    public function calculateMetrics(bool $paper = true): array
    {
        $trades = Trade::where('is_paper', $paper)->get();
        
        if ($trades->isEmpty()) {
            return ['error' => 'No trades found'];
        }
        
        $closed = $trades->where('status', 'closed');
        $winners = $closed->where('pnl', '>', 0);
        $losers = $closed->where('pnl', '<=', 0);
        
        $totalTrades = $closed->count();
        
        return [
            'overview' => [
                'total_trades' => $totalTrades,
                'winning_trades' => $winners->count(),
                'losing_trades' => $losers->count(),
                'win_rate' => $totalTrades > 0 ? $winners->count() / $totalTrades : 0,
            ],
            'performance' => [
                'total_pnl' => $closed->sum('pnl'),
                'avg_pnl' => $closed->avg('pnl'),
                'avg_win' => $winners->avg('pnl') ?? 0,
                'avg_loss' => $losers->avg('pnl') ?? 0,
                'largest_win' => $winners->max('pnl') ?? 0,
                'largest_loss' => $losers->min('pnl') ?? 0,
                'profit_factor' => $this->profitFactor($winners, $losers),
            ],
            'risk_metrics' => [
                'sharpe_ratio' => $this->calculateSharpe($closed),
                'max_drawdown' => $this->calculateMaxDrawdown($paper),
                'avg_trade_duration' => $closed->avg(fn($t) => $t->duration ? strtotime($t->duration) : 0),
            ],
            'by_strategy' => $this->metricsByStrategy($closed),
            'by_symbol' => $this->metricsBySymbol($closed),
        ];
    }

    private function profitFactor($winners, $losers): float
    {
        $grossProfit = $winners->sum('pnl');
        $grossLoss = abs($losers->sum('pnl'));
        
        return $grossLoss > 0 ? $grossProfit / $grossLoss : ($grossProfit > 0 ? INF : 0);
    }

    private function calculateSharpe($closed): float
    {
        if ($closed->count() < 5) return 0;
        
        $returns = $closed->pluck('pnl_pct')->toArray();
        $avgReturn = array_sum($returns) / count($returns);
        
        $variance = array_sum(array_map(fn($r) => pow($r - $avgReturn, 2), $returns)) / count($returns);
        $stdDev = sqrt($variance);
        
        return $stdDev > 0 ? $avgReturn / $stdDev : 0;
    }

    private function calculateMaxDrawdown(bool $paper): float
    {
        $snapshots = PortfolioSnapshot::where('is_paper', $paper)
            ->orderBy('captured_at')
            ->pluck('total_value')
            ->toArray();
        
        if (empty($snapshots)) return 0;
        
        $peak = $snapshots[0];
        $maxDD = 0;
        
        foreach ($snapshots as $value) {
            if ($value > $peak) $peak = $value;
            $drawdown = ($peak - $value) / $peak;
            if ($drawdown > $maxDD) $maxDD = $drawdown;
        }
        
        return $maxDD;
    }

    private function metricsByStrategy($trades): array
    {
        return $trades->groupBy('strategy')
            ->map(fn($group) => [
                'count' => $group->count(),
                'wins' => $group->where('pnl', '>', 0)->count(),
                'total_pnl' => $group->sum('pnl'),
                'avg_pnl' => $group->avg('pnl'),
            ])
            ->toArray();
    }

    private function metricsBySymbol($trades): array
    {
        return $trades->groupBy('symbol')
            ->map(fn($group) => [
                'count' => $group->count(),
                'wins' => $group->where('pnl', '>', 0)->count(),
                'total_pnl' => $group->sum('pnl'),
            ])
            ->toArray();
    }

    /**
     * Capture portfolio snapshot
     */
    public function captureSnapshot(array $portfolio, bool $paper = true, ?int $agentId = null): PortfolioSnapshot
    {
        return PortfolioSnapshot::create([
            'total_value' => $portfolio['total_value'],
            'cash_balance' => $portfolio['cash'],
            'positions_value' => $portfolio['positions_value'],
            'day_pnl' => $portfolio['unrealized_pnl'],
            'total_pnl' => $portfolio['realized_pnl'],
            'positions' => $portfolio['positions'],
            'allocation' => $this->calculateAllocation($portfolio),
            'open_positions_count' => $portfolio['open_positions'],
            'is_paper' => $paper,
            'agent_id' => $agentId,
            'captured_at' => now(),
        ]);
    }

    private function calculateAllocation(array $portfolio): array
    {
        if ($portfolio['total_value'] <= 0) return [];
        
        $allocation = [];
        foreach ($portfolio['positions'] as $symbol => $pos) {
            $allocation[$symbol] = ($pos['quantity'] * ($pos['current_price'] ?? $pos['entry_price'])) / $portfolio['total_value'];
        }
        
        return $allocation;
    }
}
