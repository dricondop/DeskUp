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
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('auto_notifications_enabled')->default(true);
            $table->integer('sitting_time_threshold')->default(30); // minutes
            $table->timestamps();
        });

        // Insert default settings
        DB::table('notification_settings')->insert([
            'auto_notifications_enabled' => true,
            'sitting_time_threshold' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
