<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("voice_sessions", function (Blueprint $table) {
            $table->id();
            $table->string("session_uuid", 64)->unique()->index();
            $table->foreignId("user_id")->nullable()->constrained()->onDelete("cascade");
            $table->foreignId("agent_id")->nullable()->constrained("agents")->onDelete("cascade");
            $table->string("audio_input_path", 512)->nullable();
            $table->text("transcription_text")->nullable();
            $table->string("detected_language", 10)->nullable();
            $table->decimal("stt_confidence", 3, 2)->nullable();
            $table->text("tts_text")->nullable();
            $table->string("tts_voice_id", 64)->nullable();
            $table->string("tts_audio_path", 512)->nullable();
            $table->enum("status", ["pending", "processing", "completed", "failed"])->default("pending");
            $table->json("metadata")->nullable();
            $table->text("error_message")->nullable();
            $table->timestamp("stt_completed_at")->nullable();
            $table->timestamp("tts_completed_at")->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("voice_sessions");
    }
};
