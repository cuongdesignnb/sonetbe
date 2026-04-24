<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('course_payments') || !Schema::hasTable('ebooks')) {
            return;
        }

        if (!Schema::hasColumn('course_payments', 'ebook_id')) {
            return;
        }

        try {
            Schema::table('course_payments', function (Blueprint $table) {
                $table->foreign('ebook_id')
                    ->references('id')
                    ->on('ebooks')
                    ->nullOnDelete();
            });
        } catch (\Throwable $e) {
            // Ignore when FK already exists or legacy schemas differ.
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('course_payments') || !Schema::hasColumn('course_payments', 'ebook_id')) {
            return;
        }

        try {
            Schema::table('course_payments', function (Blueprint $table) {
                $table->dropForeign(['ebook_id']);
            });
        } catch (\Throwable $e) {
            // ignore
        }
    }
};
