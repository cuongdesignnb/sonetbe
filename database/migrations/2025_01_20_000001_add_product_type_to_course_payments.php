<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('course_payments', 'product_type')) {
                $table->string('product_type', 20)->default('course')->after('id');
            }

            if (!Schema::hasColumn('course_payments', 'ebook_id')) {
                $table->unsignedBigInteger('ebook_id')->nullable()->after('course_id');
            }
        });

        // Create index when missing; ignore if already present.
        try {
            Schema::table('course_payments', function (Blueprint $table) {
                $table->index('product_type');
            });
        } catch (\Throwable $e) {
            // ignore
        }

        // `ebooks` table may be created in a later migration on fresh installs.
        // Add FK only when the target table already exists.
        if (Schema::hasTable('ebooks') && Schema::hasColumn('course_payments', 'ebook_id')) {
            try {
                Schema::table('course_payments', function (Blueprint $table) {
                    $table->foreign('ebook_id')->references('id')->on('ebooks')->nullOnDelete();
                });
            } catch (\Throwable $e) {
                // ignore when FK already exists
            }
        }
    }

    public function down(): void
    {
        Schema::table('course_payments', function (Blueprint $table) {
            // FK may not exist on environments where `ebooks` was unavailable
            // when this migration originally ran.
            try {
                $table->dropForeign(['ebook_id']);
            } catch (\Throwable $e) {
                // ignore
            }

            try {
                $table->dropIndex(['product_type']);
            } catch (\Throwable $e) {
                // ignore
            }

            if (Schema::hasColumn('course_payments', 'product_type')) {
                $table->dropColumn('product_type');
            }

            if (Schema::hasColumn('course_payments', 'ebook_id')) {
                $table->dropColumn('ebook_id');
            }
        });
    }
};
