<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('canvas_data')->nullable(); // Stores node positions for UI
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
        });

        Schema::create('workflow_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->onDelete('cascade');
            $table->string('node_id'); // Unique ID within the workflow
            $table->enum('type', ['trigger', 'action', 'condition']);
            $table->string('action_type'); // schedule, webhook, shell, api, jerry, email, if_else
            $table->json('config')->nullable(); // Node-specific configuration
            $table->json('position')->nullable(); // {x, y} for canvas
            $table->timestamps();

            $table->unique(['workflow_id', 'node_id']);
        });

        Schema::create('workflow_edges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->onDelete('cascade');
            $table->string('source_node_id');
            $table->string('target_node_id');
            $table->string('source_handle')->nullable(); // For conditions: 'true' or 'false'
            $table->timestamps();

            $table->unique(['workflow_id', 'source_node_id', 'target_node_id', 'source_handle']);
        });

        Schema::create('workflow_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['running', 'completed', 'failed'])->default('running');
            $table->json('logs')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_runs');
        Schema::dropIfExists('workflow_edges');
        Schema::dropIfExists('workflow_nodes');
        Schema::dropIfExists('workflows');
    }
};
