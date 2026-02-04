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
        Schema::create('kanban_tasks', function (Blueprint $header) {
            $header->id();
            $header->string('title');
            $header->text('description')->nullable();
            $header->string('status')->default('todo'); // todo, in_progress, done
            $header->string('priority')->default('medium'); // low, medium, high
            $header->integer('order')->default(0);
            $header->foreignId('agent_id')->nullable()->constrained()->onDelete('set null');
            $header->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kanban_tasks');
    }
};
