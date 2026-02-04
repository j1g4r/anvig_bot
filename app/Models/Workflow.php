<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workflow extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'is_active',
        'canvas_data',
        'last_run_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'canvas_data' => 'array',
        'last_run_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function nodes(): HasMany
    {
        return $this->hasMany(WorkflowNode::class);
    }

    public function edges(): HasMany
    {
        return $this->hasMany(WorkflowEdge::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(WorkflowRun::class);
    }

    public function getTriggerNode(): ?WorkflowNode
    {
        return $this->nodes()->where('type', 'trigger')->first();
    }
}
