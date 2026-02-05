<?php

namespace App\Services\Trading;

use Illuminate\Support\Facades\Log;

class RiskManager
{
    private array $limits;
    
    public function __construct()
    {
        $this->limits = [
            'max_position_pct' => config('trading.risk.max_position_pct', 0.1), // 10% per position
            'max_portfolio_risk' => config('trading.risk.max_portfolio_risk', 0.02), // 2% max loss per trade
            'max_correlated_positions' => config('trading.risk.max_correlated_positions', 3),
            'min_confidence' => config('trading.risk.min_confidence', 0.65),
            'max_open_positions' => config('trading.risk.max_open_positions', 5),
            'daily_loss_limit' => config('trading.risk.daily_loss_limit', 0.05), // 5% daily stop
        ];
    }

    /**
     * Calculate position size using Kelly Criterion variant
     */
    public function calculatePositionSize(
        float $availableCapital,
        float $price,
        float $edge, // Edge/probability of winning (0-1)
        float $fractionalKelly = 0.5, // Half Kelly for safety
    ): float {
        // Edge adjusted confidence
        $p = min(max($edge, 0.51), 0.95); // Cap between 51% and 95%
        $q = 1 - $p;
        
        // Simplified Kelly: f* = (p - q) / b where b = 1 for 1:1 risk/reward
        // Using fractional Kelly for safety
        $kellyPct = ($p - $q) / 1 * $fractionalKelly;
        
        // Apply max position limit
        $maxPosition = $availableCapital * $this->limits['max_position_pct'];
        $kellyPosition = $availableCapital * $kellyPct;
        
        $positionValue = min($kellyPosition, $maxPosition);
        
        // Convert to quantity
        $quantity = floor($positionValue / $price * 1000000) / 1000000; // 6 decimal precision for crypto
        
        Log::debug("RiskManager: Position sizing", [
            'kelly_pct' => round($kellyPct, 4),
            'max_position' => $maxPosition,
            'position_value' => $positionValue,
            'quantity' => $quantity,
        ]);
        
        return max(0, $quantity);
    }

    /**
     * Calculate stop loss price based on risk percentage
     */
    public function calculateStopLoss(float $entryPrice, float $riskPct, string $side = 'buy'): float
    {
        $stopDistance = $entryPrice * $riskPct;
        return $side === 'buy' 
            ? $entryPrice - $stopDistance  
            : $entryPrice + $stopDistance;
    }

    /**
     * Check if a new position should be allowed
     */
    public function canOpenPosition(
        array $currentPositions,
        string $newSymbol,
        float $confidence,
        float $portfolioValue,
    ): array {
        // Check confidence threshold
        if ($confidence < $this->limits['min_confidence']) {
            return ['allowed' => false, 'reason' => 'Confidence below threshold'];
        }
        
        // Check max open positions
        if (count($currentPositions) >= $this->limits['max_open_positions']) {
            return ['allowed' => false, 'reason' => 'Max open positions reached'];
        }
        
        // Check for existing position in same symbol
        foreach ($currentPositions as $pos) {
            if ($pos['symbol'] === $newSymbol) {
                return ['allowed' => false, 'reason' => 'Already have position in this symbol'];
            }
        }
        
        // Check daily loss limit
        $dailyPnl = $this->getDailyPnL();
        if ($portfolioValue > 0 && ($dailyPnl / $portfolioValue) < -$this->limits['daily_loss_limit']) {
            return ['allowed' => false, 'reason' => 'Daily loss limit reached'];
        }
        
        return ['allowed' => true];
    }

    /**
     * Calculate portfolio heat (aggregate risk exposure)
     */
    public function calculatePortfolioHeat(array $positions): float
    {
        $heat = 0;
        foreach ($positions as $pos) {
            if (!isset($pos['entry_price']) || !isset($pos['current_price'])) continue;
            
            $riskDistance = abs($pos['entry_price'] - $pos['current_price']) / $pos['entry_price'];
            $heat += $riskDistance * ($pos['value'] ?? 0);
        }
        
        return $heat;
    }

    /**
     * Get daily realized PnL
     */
    private function getDailyPnL(): float
    {
        return \App\Models\Trade::whereDate('closed_at', today())
            ->paper()
            ->sum('pnl') ?? 0;
    }

    /**
     * Assess signal quality and risk-adjusted confidence
     */
    public function assessSignal(array $indicators, array $marketData): float
    {
        $baseConfidence = 0.5;
        
        // RSI extremes increase confidence
        if (isset($indicators['rsi'])) {
            $rsi = $indicators['rsi'];
            if ($rsi < 30) $baseConfidence += 0.15; // Oversold = bullish
            if ($rsi > 70) $baseConfidence -= 0.15; // Overbought = bearish
        }
        
        // Trend alignment
        if (isset($indicators['sma'])) {
            $sma20 = $indicators['sma']['20'] ?? null;
            $sma50 = $indicators['sma']['50'] ?? null;
            $price = end($marketData)['close'] ?? 0;
            
            if ($sma20 && $sma50) {
                if ($price > $sma20 && $sma20 > $sma50) {
                    $baseConfidence += 0.1; // Uptrend
                } elseif ($price < $sma20 && $sma20 < $sma50) {
                    $baseConfidence -= 0.1; // Downtrend
                }
            }
        }
        
        // MACD confirmation
        if (isset($indicators['macd'])) {
            $macd = $indicators['macd']['macd'] ?? 0;
            $signal = $indicators['macd']['signal'] ?? 0;
            $histogram = $indicators['macd']['histogram'] ?? 0;
            
            if ($macd > $signal && $histogram > 0) {
                $baseConfidence += 0.1;
            } elseif ($macd < $signal && $histogram < 0) {
                $baseConfidence -= 0.1;
            }
        }
        
        // Cap at 0.95, floor at 0.05
        return min(max($baseConfidence, 0.05), 0.95);
    }
}
