<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 20)->index();
            $table->enum('side', ['buy', 'sell']);
            $table->enum('action', ['open', 'close', 'modify']);
            $table->enum('type', ['market', 'limit', 'stop'])->default('market');
            $table->decimal('quantity', 18, 8);
            $table->decimal('entry_price', 18, 8)->nullable();
            $table->decimal('exit_price', 18, 8)->nullable();
            $table->decimal('current_price', 18, 8)->nullable();
            $table->decimal('stop_loss', 18, 8)->nullable();
            $table->decimal('take_profit', 18, 8)->nullable();
            $table->decimal('pnl', 18, 8)->default(0); // Profit/Loss
            $table->decimal('pnl_pct', 8, 4)->default(0);
            $table->decimal('fees', 18, 8)->default(0);
            $table->string('strategy', 50)->index(); // 'momentum', 'mean_reversion', 'ai'
            $table->decimal('confidence', 5, 4); // 0.00 - 1.00
            $table->text('reasoning')->nullable();
            $table->enum('status', ['pending', 'open', 'closed', 'cancelled', 'failed'])->default('pending');
            $table->string('provider', 20)->nullable(); // 'binance', 'coinbase', 'paper'
            $table->string('provider_trade_id', 100)->nullable();
            $table->boolean('is_paper')->default(true);
            $table->unsignedBigInteger('agent_id')->nullable()->index();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            
            $table->index(['symbol', 'status']);
            $table->index(['created_at', 'strategy']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
