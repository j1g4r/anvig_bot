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
        Schema::create('agent_cron_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('command'); // e.g. "agent:research"
            $table->text('params')->nullable(); // JSON arguments
            $table->string('schedule_expression'); // Cron string "* * * * *"
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('creator_agent_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_cron_jobs');
    }
};
