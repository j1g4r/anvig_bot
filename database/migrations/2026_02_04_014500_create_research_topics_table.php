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
        Schema::create('research_topics', function (Blueprint $table) {
            $table->id();
            $table->string('topic'); // e.g., "Vector Database Options"
            $table->string('status')->default('pending'); // pending, researched, proposed, rejected, adopted
            $table->text('findings')->nullable(); // Markdown summary
            $table->decimal('relevance_score', 5, 4)->nullable(); // 0.0000 - 1.0000
            $table->string('source_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('research_topics');
    }
};
