<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->unsignedBigInteger('duration_tier_id')->nullable()->after('course_id');
            $table->timestamp('expires_at')->nullable()->after('completed_at');

            $table->foreign('duration_tier_id')
                  ->references('id')
                  ->on('course_duration_tiers')
                  ->onDelete('set null');

            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign(['duration_tier_id']);
            $table->dropIndex(['expires_at']);
            $table->dropColumn(['duration_tier_id', 'expires_at']);
        });
    }
};
