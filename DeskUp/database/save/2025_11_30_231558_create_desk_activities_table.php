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
        Schema::create('desk_activities', function (Blueprint $table) {
            $table->id();

            // The desk this activity belongs to
            $table->foreignId('desk_id')
                ->constrained('desks')
                ->onDelete('cascade');

            // The user that created / owns the activity (optional)
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->onDelete('set null');

            // When the activity is scheduled (you use this in statistics)
            $table->timestamp('scheduled_at')->nullable();

            // Optional end time
            $table->timestamp('scheduled_to')->nullable();

            // Optional type/label, if you need it
            $table->string('type')->nullable();

            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('desk_activities');
    }
};
