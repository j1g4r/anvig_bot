<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class VideoAnalysisSession extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'source', 'fps', 'status', 
        'started_at', 'ended_at', 'events_summary', 'duration_seconds'
    ];

    protected $casts = [
        'events_summary' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'fps' => 'integer',
        'duration_seconds' => 'integer',
    ];
}
