<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KanbanTask extends Model
{
    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'order',
        'agent_id',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    protected static function booted()
    {
        static::saved(function ($task) {
            broadcast(new \App\Events\KanbanTaskUpdated($task));
        });
    }
}
