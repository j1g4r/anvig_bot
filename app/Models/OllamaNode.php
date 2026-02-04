<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OllamaNode extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'host',
        'port',
        'status',
        'models',
        'active_requests',
        'max_concurrent',
        'avg_response_time',
        'last_seen_at',
        'is_primary',
    ];

    protected $casts = [
        'models' => 'array',
        'is_primary' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getUrlAttribute(): string
    {
        return "http://{$this->host}:{$this->port}";
    }

    public function isAvailable(): bool
    {
        return $this->status === 'online' && $this->active_requests < $this->max_concurrent;
    }

    public function hasModel(string $model): bool
    {
        return in_array($model, $this->models ?? []);
    }

    public function getLoad(): float
    {
        if ($this->max_concurrent === 0) return 1.0;
        return $this->active_requests / $this->max_concurrent;
    }
}
