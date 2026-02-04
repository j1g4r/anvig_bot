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
        Schema::create('global_knowledge_pool', function (Blueprint $table) {
            $table->id();
            $table->string('pattern_hash')->unique();
            $table->text('pattern_json'); // Abstract instruction
            $table->decimal('global_score', 5, 4)->default(0); // 0.0000 to 1.0000
            $table->integer('usage_count')->default(0);
            $table->json('contributors')->nullable(); // List of agent IDs
            $table->timestamps();
            
            $table->index('global_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global_knowledge_pool');
    }
};
