<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smart_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('room')->nullable();
            $table->enum('type', ['light', 'switch', 'thermostat', 'sensor', 'lock', 'cover', 'other']);
            $table->enum('protocol', ['mqtt', 'http', 'home_assistant']);
            $table->json('config'); // Protocol-specific config
            $table->boolean('is_online')->default(false);
            $table->json('state')->nullable(); // Current state
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smart_devices');
    }
};
