<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vision_analysis_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->string('session_id')->unique();
            $table->string('stream_name')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->integer('total_frames')->default(0);
            $table->integer('processed_frames')->default(0);
            $table->text('analysis_summary')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['conversation_id', 'status']);
            $table->index('session_id');
        });

        Schema::create('vision_frame_analysis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('vision_analysis_sessions')->onDelete('cascade');
            $table->integer('frame_number');
            $table->string('frame_hash');
            $table->text('description')->nullable();
            $table->text('raw_response')->nullable();
            $table->json('detected_objects')->nullable();
            $table->json('analysis_metadata')->nullable();
            $table->decimal('processing_duration_ms', 10, 2)->nullable();
            $table->timestamp('analyzed_at');
            $table->timestamps();
            
            $table->unique(['session_id', 'frame_number']);
            $table->index('frame_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vision_frame_analysis');
        Schema::dropIfExists('vision_analysis_sessions');
    }
};