<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class AgentCronJob extends Model
{
    protected $fillable = [
        'command',
        'params',
        'schedule_expression',
        'description',
        'is_active',
        'creator_agent_id',
    ];

    protected $casts = [
        'params' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
