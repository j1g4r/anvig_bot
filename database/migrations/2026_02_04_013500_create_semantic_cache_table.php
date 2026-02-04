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
        Schema::create('semantic_cache', function (Blueprint $table) {
            $table->id();
            $table->text('query_text'); // The actual query
            $table->string('query_hash')->index(); // Fast lookup hash (e.g., md5 of normalized text)
            $table->text('response'); // The generated response
            $table->integer('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();
            
            // In a real vector system, we'd have 'embedding' column here.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('semantic_cache');
    }
};
