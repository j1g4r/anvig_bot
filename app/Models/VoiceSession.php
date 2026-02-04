<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoiceSession extends Model
{
    protected $table = "voice_sessions";

    protected $fillable = [
        "session_uuid",
        "user_id",
        "agent_id",
        "audio_input_path",
        "transcription_text",
        "detected_language",
        "stt_confidence",
        "stt_completed_at",
        "tts_text",
        "tts_voice_id",
        "tts_audio_path",
        "tts_completed_at",
        "status",
        "metadata",
        "error_message",
    ];

    protected $casts = [
        "metadata" => "array",
        "stt_confidence" => "float",
        "stt_completed_at" => "datetime",
        "tts_completed_at" => "datetime",
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
