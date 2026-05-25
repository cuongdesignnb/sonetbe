<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop old foreign keys if they exist
        try {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->dropForeign('enrollments_user_id_foreign');
            });
        } catch (\Throwable $e) {}

        try {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->dropForeign('enrollments_course_id_foreign');
            });
        } catch (\Throwable $e) {}

        // 2. Drop old unique index if it exists
        try {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->dropUnique('enrollments_user_id_course_id_unique');
            });
        } catch (\Throwable $e) {}

        // 3. Add section_id column and foreign key if they do not exist
        Schema::table('enrollments', function (Blueprint $table) {
            if (!Schema::hasColumn('enrollments', 'section_id')) {
                $table->unsignedBigInteger('section_id')->nullable()->after('course_id');
                $table->foreign('section_id')->references('id')->on('course_sections')->nullOnDelete();
            }
        });

        // 4. Re-create foreign keys and unique index
        try {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        } catch (\Throwable $e) {}

        try {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
            });
        } catch (\Throwable $e) {}

        try {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->unique(['user_id', 'course_id', 'section_id']);
            });
        } catch (\Throwable $e) {}
    }

    public function down(): void
    {
        try {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->dropUnique(['user_id', 'course_id', 'section_id']);
            });
        } catch (\Throwable $e) {}

        try {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->dropForeign(['section_id']);
            });
        } catch (\Throwable $e) {}

        try {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->dropColumn('section_id');
            });
        } catch (\Throwable $e) {}

        try {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->unique(['user_id', 'course_id']);
            });
        } catch (\Throwable $e) {}
    }
};
