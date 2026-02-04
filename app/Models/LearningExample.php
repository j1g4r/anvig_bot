<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class LearningExample extends Model
{
    protected $fillable = [
        'agent_id',
        'conversation_id',
        'message_id',
        'user_input',
        'agent_output',
        'feedback_score',
        'feedback_type',
        'context_embedding',
        'metadata',
    ];

    protected $casts = [
        'feedback_score' => 'float',
        'metadata' => 'array',
    ];

    // Relationships
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    // Scopes
    public function scopePositive(Builder $query): Builder
    {
        return $query->where('feedback_score', '>', 0);
    }

    public function scopeNegative(Builder $query): Builder
    {
        return $query->where('feedback_score', '<', 0);
    }

    public function scopeNeutral(Builder $query): Builder
    {
        return $query->where('feedback_score', 0);
    }

    public function scopeExplicit(Builder $query): Builder
    {
        return $query->where('feedback_type', 'explicit');
    }

    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Helpers
    public function isPositive(): bool
    {
        return $this->feedback_score > 0;
    }

    public function isNegative(): bool
    {
        return $this->feedback_score < 0;
    }

    public function getQualityLabel(): string
    {
        if ($this->feedback_score >= 0.5) return 'excellent';
        if ($this->feedback_score > 0) return 'good';
        if ($this->feedback_score == 0) return 'neutral';
        if ($this->feedback_score > -0.5) return 'poor';
        return 'bad';
    }
}
