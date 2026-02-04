<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class AgentAdaptation extends Model
{
    protected $fillable = [
        'agent_id',
        'adaptation_type',
        'name',
        'description',
        'pattern',
        'weight',
        'effectiveness',
        'active',
    ];

    protected $casts = [
        'pattern' => 'array',
        'weight' => 'float',
        'effectiveness' => 'float',
        'active' => 'boolean',
    ];

    // Constants for adaptation types
    public const TYPE_STYLE = 'style';
    public const TYPE_PREFERENCE = 'preference';
    public const TYPE_RULE = 'rule';
    public const TYPE_PATTERN = 'pattern';

    // Relationships
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('adaptation_type', $type);
    }

    public function scopeEffective(Builder $query, float $threshold = 0.5): Builder
    {
        return $query->where('effectiveness', '>=', $threshold);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('weight');
    }

    // Helpers
    public function activate(): void
    {
        $this->update(['active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['active' => false]);
    }

    public function updateEffectiveness(float $score): void
    {
        // Exponential moving average for effectiveness
        $alpha = 0.3;
        $currentEffectiveness = $this->effectiveness ?? $score;
        $newEffectiveness = ($alpha * $score) + ((1 - $alpha) * $currentEffectiveness);
        
        $this->update(['effectiveness' => $newEffectiveness]);
    }

    /**
     * Convert pattern to a system prompt instruction.
     */
    public function toPromptInstruction(): string
    {
        $pattern = $this->pattern;
        
        return match ($this->adaptation_type) {
            self::TYPE_STYLE => "Communication style: {$pattern['instruction']}",
            self::TYPE_PREFERENCE => "User preference: {$pattern['instruction']}",
            self::TYPE_RULE => "Behavioral rule: {$pattern['instruction']}",
            self::TYPE_PATTERN => "Learned pattern: {$pattern['instruction']}",
            default => $pattern['instruction'] ?? '',
        };
    }
}
