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
        Schema::create('user_patterns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action_type'); // e.g. 'check_status', 'chat_agent'
            $table->string('context_key'); // e.g. '09:00|weekday', 'login|after'
            $table->integer('frequency')->default(1);
            $table->decimal('confidence', 5, 4)->default(0); // 0.0000 - 1.0000
            $table->timestamp('last_occurrence_at')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'action_type', 'context_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_patterns');
    }
};
