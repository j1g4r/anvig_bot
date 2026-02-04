<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Learning Examples - Store interaction pairs with feedback
        Schema::create('learning_examples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('message_id')->nullable();
            $table->text('user_input');
            $table->text('agent_output');
            $table->decimal('feedback_score', 3, 2)->default(0); // -1.00 to 1.00
            $table->string('feedback_type')->nullable(); // 'explicit', 'implicit', 'tool_success', 'tool_failure'
            $table->binary('context_embedding')->nullable(); // Binary vector for similarity search
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['agent_id', 'feedback_score']);
            $table->index(['agent_id', 'created_at']);
        });

        // Learning Sessions - Track training runs
        Schema::create('learning_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->integer('examples_processed')->default(0);
            $table->json('improvements')->nullable(); // Learned patterns summary
            $table->json('metrics')->nullable(); // Performance metrics
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['agent_id', 'status']);
        });

        // Agent Adaptations - Store learned behavioral patterns
        Schema::create('agent_adaptations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->string('adaptation_type'); // 'style', 'preference', 'rule', 'pattern'
            $table->string('name'); // Human-readable name
            $table->text('description')->nullable();
            $table->json('pattern'); // The learned behavior/rule
            $table->decimal('weight', 5, 4)->default(1.0000); // Priority/confidence weight
            $table->decimal('effectiveness', 5, 4)->nullable(); // Measured effectiveness
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['agent_id', 'active']);
            $table->index(['agent_id', 'adaptation_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_adaptations');
        Schema::dropIfExists('learning_sessions');
        Schema::dropIfExists('learning_examples');
    }
};
