<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoStreamFrame extends Model
{
    use HasUuids;

    public $timestamps = false;
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'session_id', 'frame_base64', 'timestamp', 
        'analysis_result', 'objects_detected', 'description'
    ];

    protected $casts = [
        'timestamp' => 'float',
        'analysis_result' => 'array',
        'objects_detected' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(VideoAnalysisSession::class, 'session_id');
    }
}
