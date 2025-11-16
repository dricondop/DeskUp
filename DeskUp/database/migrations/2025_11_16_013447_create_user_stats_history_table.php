<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_stats_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('desk_id')->constrained()->onDelete('cascade');
            $table->integer('desk_height_mm');
            $table->integer('desk_speed_mms')->default(0);
            $table->string('desk_status')->default('Normal');
            $table->boolean('is_position_lost')->default(false);
            $table->boolean('is_overload_up')->default(false);
            $table->boolean('is_overload_down')->default(false);
            $table->boolean('is_anti_collision')->default(false);
            $table->integer('activations_count')->default(0);
            $table->integer('sit_stand_count')->default(0);
            $table->timestamp('recorded_at');
            // THIS CREATES THE created_at and updated_at columns: $table->timestamps();
            
            $table->index(['user_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_stats_history');
    }
};
