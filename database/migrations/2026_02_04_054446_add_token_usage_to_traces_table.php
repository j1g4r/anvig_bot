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
        Schema::table('traces', function (Blueprint $table) {
            $table->integer('tokens_input')->nullable()->after('status');
            $table->integer('tokens_output')->nullable()->after('tokens_input');
            $table->integer('tokens_total')->nullable()->after('tokens_output');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('traces', function (Blueprint $table) {
            //
        });
    }
};
