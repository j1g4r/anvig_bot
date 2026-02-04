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
        Schema::table('messages', function (Blueprint $table) {
            $table->string('sentiment')->nullable()->after('content'); // positive, negative, neutral
            $table->decimal('sentiment_score', 5, 4)->nullable()->after('sentiment'); // -1.0 to 1.0
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['sentiment', 'sentiment_score']);
        });
    }
};
