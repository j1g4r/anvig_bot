<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trading_strategies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('slug', 50)->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['momentum', 'mean_reversion', 'trend_following', 'ai_prediction', 'arbitrage']);
            $table->json('parameters'); // Strategy-specific config
            $table->json('symbols'); // Symbols this strategy monitors
            $table->boolean('is_active')->default(true);
            $table->decimal('win_rate', 5, 4)->default(0); // Historical win rate
            $table->decimal('avg_return', 8, 4)->default(0);
            $table->decimal('total_pnl', 18, 8)->default(0);
            $table->integer('trades_count')->default(0);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('last_signal_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trading_strategies');
    }
};
