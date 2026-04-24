<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->string('reviewer_name')->nullable()->after('course_id');
            $table->boolean('is_approved')->default(false)->after('comment');
            $table->timestamp('approved_at')->nullable()->after('is_approved');
        });

        DB::statement('ALTER TABLE reviews MODIFY user_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('approved_at');
            $table->dropColumn('is_approved');
            $table->dropColumn('reviewer_name');
        });

        DB::statement('ALTER TABLE reviews MODIFY user_id BIGINT UNSIGNED NOT NULL');
    }
};
