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
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint')->default('openai'); // 'openai', 'anthropic', etc
            $table->string('model')->nullable();
            $table->integer('tokens_input')->default(0);
            $table->integer('tokens_output')->default(0);
            $table->integer('tokens_total')->default(0);
            $table->decimal('cost', 10, 6)->default(0); // Estimated cost in USD
            $table->json('metadata')->nullable(); // Context (e.g., related conversation_id)
            $table->timestamps();
            
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};
