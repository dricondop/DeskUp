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
        Schema::dropIfExists('desk_activities');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('desk_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('desk_id')->constrained()->onDelete('cascade');
            $table->string('activity_type'); // e.g., 'meeting', 'cleaning', 'maintenance'
            $table->text('description')->nullable();
            $table->timestamp('scheduled_at');
            $table->timestamp('scheduled_to');
            $table->string('status')->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }
};
