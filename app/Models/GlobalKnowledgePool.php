<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalKnowledgePool extends Model
{
    protected $table = 'global_knowledge_pool';

    protected $fillable = [
        'pattern_hash',
        'pattern_json',
        'global_score',
        'usage_count',
        'contributors',
    ];

    protected $casts = [
        'global_score' => 'decimal:4',
        'contributors' => 'array',
    ];
}
