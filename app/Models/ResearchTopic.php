<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResearchTopic extends Model
{
    protected $fillable = [
        'topic',
        'status',
        'findings',
        'relevance_score',
        'source_url',
    ];

    protected $casts = [
        'relevance_score' => 'decimal:4',
    ];
}
