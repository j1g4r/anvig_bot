<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, json
            $table->timestamps();
        });

        // Seed default God Mode setting
        DB::table('system_settings')->insert([
            'key' => 'god_mode_enabled',
            'value' => 'false',
            'type' => 'boolean',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        DB::table('system_settings')->insert([
            'key' => 'autonomy_level',
            'value' => '0',
            'type' => 'integer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
