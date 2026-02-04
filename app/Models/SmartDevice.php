<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmartDevice extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'room',
        'type',
        'protocol',
        'config',
        'is_online',
        'state',
        'last_seen_at',
    ];

    protected $casts = [
        'config' => 'array',
        'state' => 'array',
        'is_online' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOn(): bool
    {
        return ($this->state['on'] ?? false) === true;
    }

    public function getStateValue(string $key, $default = null)
    {
        return $this->state[$key] ?? $default;
    }
}
