<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('course_payments', 'webinar_id')) {
                $table->unsignedBigInteger('webinar_id')->nullable()->after('ebook_id');
            }
        });

        // Add foreign key in separate call to avoid issues
        if (Schema::hasTable('webinars') && Schema::hasColumn('course_payments', 'webinar_id')) {
            Schema::table('course_payments', function (Blueprint $table) {
                try {
                    $table->foreign('webinar_id')->references('id')->on('webinars')->nullOnDelete();
                } catch (\Throwable $e) {
                    // Foreign key may already exist
                }
            });
        }

        // Make course_id and enrollment_id nullable (webinar payments don't need them)
        Schema::table('course_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('course_id')->nullable()->change();
            $table->unsignedBigInteger('enrollment_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('course_payments', function (Blueprint $table) {
            if (Schema::hasColumn('course_payments', 'webinar_id')) {
                try {
                    $table->dropForeign(['webinar_id']);
                } catch (\Throwable $e) {}
                $table->dropColumn('webinar_id');
            }
        });
    }
};
