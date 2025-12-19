<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('profile_picture')->nullable();
            $table->string('phone')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('location')->nullable();
            $table->string('ideal_height')->nullable()->comment('Ideal height in cm');
            $table->timestamps(); 
            
            $table->index('user_id');
            $table->unique('user_id'); 
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_profiles');
    }
};