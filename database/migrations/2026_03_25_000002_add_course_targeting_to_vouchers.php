<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add applicable_type to vouchers table
        Schema::table('vouchers', function (Blueprint $table) {
            $table->enum('applicable_type', ['all', 'specific'])->default('all')->after('status');
        });

        // Create pivot table for voucher <-> course
        Schema::create('voucher_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('vouchers')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['voucher_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_courses');

        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn('applicable_type');
        });
    }
};
