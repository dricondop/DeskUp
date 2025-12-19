<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('height_detections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('detected_height', 5, 2)->comment('User height in cm');
            $table->decimal('recommended_height', 5, 2)->comment('Recommended desk height in cm');
            $table->json('posture_data')->nullable()->comment('Analysis of posture data');
            $table->string('image_path')->nullable()->comment('Processed image route');
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('height_detections');
    }
};