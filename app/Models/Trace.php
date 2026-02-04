<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trace extends Model
{
    protected $fillable = [
        'conversation_id',
        'agent_id',
        'tool_name',
        'input',
        'output',
        'duration_ms',
        'status',
    ];

    protected $casts = [
        'input' => 'array',
        'output' => 'array',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
