<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_stats_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('desk_id');
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
            // Add foreign key constraint referencing desks.id (primary key)
            $table->foreign('desk_id')->references('id')->on('desks')->onDelete('cascade');
        });
        
        // Add check constraint for desk height (680-1320mm inclusive)
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE user_stats_history ADD CONSTRAINT chk_desk_height CHECK (desk_height_mm >= 680 AND desk_height_mm <= 1320)');
        } 
        
        // SQLite for testing, PGSQL does not support RAM databases
        elseif (DB::getDriverName() === 'sqlite') {
            DB::statement('CREATE TRIGGER chk_desk_height_insert BEFORE INSERT ON user_stats_history
                BEGIN
                    SELECT CASE WHEN NEW.desk_height_mm < 680 OR NEW.desk_height_mm > 1320
                    THEN RAISE(ABORT, "desk_height_mm must be between 680 and 1320")
                    END;
                END');
            DB::statement('CREATE TRIGGER chk_desk_height_update BEFORE UPDATE ON user_stats_history
                BEGIN
                    SELECT CASE WHEN NEW.desk_height_mm < 680 OR NEW.desk_height_mm > 1320
                    THEN RAISE(ABORT, "desk_height_mm must be between 680 and 1320")
                    END;
                END');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_stats_history');
    }
};
