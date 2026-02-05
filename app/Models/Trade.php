<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trade extends Model
{
    use HasFactory;

    protected $fillable = [
        'symbol', 'side', 'action', 'type',
        'quantity', 'entry_price', 'exit_price', 'current_price',
        'stop_loss', 'take_profit', 'pnl', 'pnl_pct', 'fees',
        'strategy', 'confidence', 'reasoning', 'status',
        'provider', 'provider_trade_id', 'is_paper', 'agent_id',
        'opened_at', 'closed_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:8',
        'entry_price' => 'decimal:8',
        'exit_price' => 'decimal:8',
        'current_price' => 'decimal:8',
        'stop_loss' => 'decimal:8',
        'take_profit' => 'decimal:8',
        'pnl' => 'decimal:8',
        'pnl_pct' => 'decimal:4',
        'fees' => 'decimal:8',
        'confidence' => 'decimal:4',
        'is_paper' => 'boolean',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopePaper($query)
    {
        return $query->where('is_paper', true);
    }

    public function scopeLive($query)
    {
        return $query->where('is_paper', false);
    }

    public function scopeByStrategy($query, string $strategy)
    {
        return $query->where('strategy', $strategy);
    }

    public function scopeProfitable($query)
    {
        return $query->where('pnl', '>', 0);
    }

    // Computed
    public function getDurationAttribute(): ?string
    {
        if (!$this->opened_at) return null;
        $end = $this->closed_at ?? now();
        return $this->opened_at->diffForHumans($end, true);
    }

    public function getIsProfitableAttribute(): bool
    {
        return $this->pnl > 0;
    }

    public function getRoeAttribute(): float // Return on Equity (margin)
    {
        if (!$this->entry_price || $this->entry_price == 0) return 0;
        $current = $this->exit_price ?? $this->current_price ?? $this->entry_price;
        $multiplier = $this->side === 'buy' ? 1 : -1;
        return (($current - $this->entry_price) / $this->entry_price) * $multiplier * 100;
    }

    // Relations
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    // Actions
    public function close(float $exitPrice, ?string $reason = null): self
    {
        $this->exit_price = $exitPrice;
        $this->closed_at = now();
        
        $entryValue = $this->quantity * $this->entry_price;
        $exitValue = $this->quantity * $exitPrice;
        $multiplier = $this->side === 'buy' ? 1 : -1;
        
        $this->pnl = ($exitValue - $entryValue) * $multiplier - $this->fees;
        $this->pnl_pct = $entryValue > 0 ? ($this->pnl / $entryValue) * 100 : 0;
        $this->status = 'closed';
        
        if ($reason) {
            $this->reasoning = ($this->reasoning ?? '') . "\n[Close: {$reason}]";
        }
        
        $this->save();
        return $this;
    }

    public function updateCurrentPrice(float $price): self
    {
        $this->current_price = $price;
        
        // Auto-close if stop loss or take profit hit
        if ($this->status === 'open') {
            if ($this->stop_loss && $this->side === 'buy' && $price <= $this->stop_loss) {
                $this->close($price, 'Stop loss triggered');
            } elseif ($this->stop_loss && $this->side === 'sell' && $price >= $this->stop_loss) {
                $this->close($price, 'Stop loss triggered');
            } elseif ($this->take_profit && $this->side === 'buy' && $price >= $this->take_profit) {
                $this->close($price, 'Take profit triggered');
            } elseif ($this->take_profit && $this->side === 'sell' && $price <= $this->take_profit) {
                $this->close($price, 'Take profit triggered');
            }
        }
        
        $this->save();
        return $this;
    }
}
