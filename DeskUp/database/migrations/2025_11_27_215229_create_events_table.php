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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type'); // e.g. 'meeting', 'cleaning', 'event', 'maintenance'
            $table->string('description')->nullable();
            $table->timestamp('scheduled_at');
            $table->timestamp('scheduled_to')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        // Fix any NULL scheduled_at values by using created_at as fallback
        DB::statement('UPDATE events SET scheduled_at = created_at WHERE scheduled_at IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
