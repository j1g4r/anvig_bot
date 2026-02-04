<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('agent_id')->constrained()->onDelete('cascade');
            $table->string('color')->nullable(); // For visual distinction
            $table->boolean('is_primary')->default(false); // Primary responder
            $table->integer('order')->default(0); // Response order
            $table->timestamps();

            $table->unique(['conversation_id', 'agent_id']);
        });

        // Add multi-agent flag to conversations
        Schema::table('conversations', function (Blueprint $table) {
            $table->boolean('is_multi_agent')->default(false)->after('agent_id');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('is_multi_agent');
        });
        Schema::dropIfExists('chat_participants');
    }
};
