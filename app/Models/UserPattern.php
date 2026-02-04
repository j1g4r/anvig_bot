<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPattern extends Model
{
    protected $fillable = [
        'user_id',
        'action_type',
        'context_key',
        'frequency',
        'confidence',
        'last_occurrence_at',
    ];

    protected $casts = [
        'confidence' => 'decimal:4',
        'last_occurrence_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
