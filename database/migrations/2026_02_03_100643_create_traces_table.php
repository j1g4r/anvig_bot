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
        Schema::create('traces', function (Blueprint $header) {
            $header->id();
            $header->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $header->foreignId('agent_id')->constrained()->onDelete('cascade');
            $header->string('tool_name');
            $header->json('input')->nullable();
            $header->json('output')->nullable();
            $header->integer('duration_ms')->nullable();
            $header->string('status')->default('success'); // success, error
            $header->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('traces');
    }
};
