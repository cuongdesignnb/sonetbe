<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->unsignedBigInteger('section_id')->nullable()->after('course_id');

            $table->foreign('section_id')->references('id')->on('course_sections')->onDelete('cascade');
            $table->index(['section_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropForeign(['section_id']);
            $table->dropIndex(['section_id', 'order']);
            $table->dropColumn('section_id');
        });
    }
};
