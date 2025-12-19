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
        Schema::table('desks', function (Blueprint $table) {
            // Add user_id column if it doesn't exist
            if (!Schema::hasColumn('desks', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            }
            // Add api_desk_id column if it doesn't exist
            if (!Schema::hasColumn('desks', 'api_desk_id')) {
                $table->string('api_desk_id')->nullable()->unique();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('desks', function (Blueprint $table) {
            if (Schema::hasColumn('desks', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
            if (Schema::hasColumn('desks', 'api_desk_id')) {
                $table->dropColumn('api_desk_id');
            }
        });
    }
};
