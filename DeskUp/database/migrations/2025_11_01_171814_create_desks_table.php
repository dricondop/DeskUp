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
        Schema::create('desks', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->integer('desk_number')->unique();
            $table->float('position_x')->nullable();
            $table->float('position_y')->nullable();
            $table->string('status')->default('OK');
            $table->integer('height')->default(110); // in cm
            $table->integer('speed')->default(36); // in mm/s
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('desks');
    }
};
