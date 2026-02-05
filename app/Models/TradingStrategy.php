<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradingStrategy extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'type', 'parameters',
        'symbols', 'is_active', 'win_rate', 'avg_return',
        'total_pnl', 'trades_count', 'last_run_at', 'last_signal_at',
    ];

    protected $casts = [
        'parameters' => 'array',
        'symbols' => 'array',
        'is_active' => 'boolean',
        'win_rate' => 'decimal:4',
        'avg_return' => 'decimal:4',
        'total_pnl' => 'decimal:8',
        'last_run_at' => 'datetime',
        'last_signal_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
