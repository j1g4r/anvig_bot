<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SemanticCache extends Model
{
    protected $table = 'semantic_cache';

    protected $fillable = [
        'query_text',
        'query_hash',
        'response',
        'hits',
        'last_hit_at',
    ];

    protected $casts = [
        'last_hit_at' => 'datetime',
    ];
}
