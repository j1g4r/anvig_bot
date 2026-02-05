<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortfolioSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'total_value', 'cash_balance', 'positions_value',
        'day_pnl', 'total_pnl', 'positions', 'allocation',
        'open_positions_count', 'is_paper', 'agent_id', 'captured_at',
    ];

    protected $casts = [
        'total_value' => 'decimal:8',
        'cash_balance' => 'decimal:8',
        'positions_value' => 'decimal:8',
        'day_pnl' => 'decimal:8',
        'total_pnl' => 'decimal:8',
        'positions' => 'array',
        'allocation' => 'array',
        'is_paper' => 'boolean',
        'captured_at' => 'datetime',
    ];

    public function scopePaper($query)
    {
        return $query->where('is_paper', true);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('captured_at', 'desc');
    }
}
