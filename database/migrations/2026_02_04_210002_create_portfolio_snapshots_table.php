<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_snapshots', function (Blueprint $table) {
            $table->id();
            $table->decimal('total_value', 18, 8); // Total portfolio value
            $table->decimal('cash_balance', 18, 8);
            $table->decimal('positions_value', 18, 8);
            $table->decimal('day_pnl', 18, 8)->default(0);
            $table->decimal('total_pnl', 18, 8)->default(0);
            $table->json('positions'); // Current positions by symbol
            $table->json('allocation'); // Percentage allocation
            $table->integer('open_positions_count')->default(0);
            $table->boolean('is_paper')->default(true);
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->timestamp('captured_at');
            $table->timestamps();
            
            $table->index('captured_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_snapshots');
    }
};
