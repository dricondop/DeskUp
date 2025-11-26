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
            $table->integer('desk_id'); // Changed from foreignId to integer
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
            
            $table->index(['user_id', 'recorded_at']);
            // Add foreign key constraint referencing desk_number
            $table->foreign('desk_id')->references('desk_number')->on('desks')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_stats_history');
    }
};
