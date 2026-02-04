<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ollama_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('host'); // e.g., localhost, 192.168.1.100
            $table->integer('port')->default(11434);
            $table->enum('status', ['online', 'offline', 'unknown'])->default('unknown');
            $table->json('models')->nullable(); // Available models on this node
            $table->integer('active_requests')->default(0);
            $table->integer('max_concurrent')->default(2);
            $table->float('avg_response_time')->nullable(); // ms
            $table->timestamp('last_seen_at')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'host', 'port']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ollama_nodes');
    }
};
