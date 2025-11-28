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
        Schema::table('desk_activities', function (Blueprint $table) {
            $table->timestamp('scheduled_to')->nullable()->after('scheduled_at');
            $table->string('status')->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('desk_activities', function (Blueprint $table) {
            $table->dropColumn('scheduled_to');
            $table->dropColumn('status');
            $table->dropConstrainedForeignId('created_by');
        });
    }
};


