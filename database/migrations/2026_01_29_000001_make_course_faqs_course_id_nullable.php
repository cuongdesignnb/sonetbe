<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_faqs', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
        });

        DB::statement('ALTER TABLE course_faqs MODIFY course_id BIGINT UNSIGNED NULL');

        Schema::table('course_faqs', function (Blueprint $table) {
            $table->foreign('course_id')
                ->references('id')
                ->on('courses')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('course_faqs', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
        });

        DB::statement('ALTER TABLE course_faqs MODIFY course_id BIGINT UNSIGNED NOT NULL');

        Schema::table('course_faqs', function (Blueprint $table) {
            $table->foreign('course_id')
                ->references('id')
                ->on('courses')
                ->cascadeOnDelete();
        });
    }
};
