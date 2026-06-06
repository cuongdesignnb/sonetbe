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
        Schema::table('courses', function (Blueprint $table) {
            $table->decimal('price', 16, 2)->default(0)->change();
            $table->decimal('original_price', 16, 2)->nullable()->change();
        });

        Schema::table('course_sections', function (Blueprint $table) {
            $table->decimal('price', 16, 2)->default(0)->change();
            $table->decimal('original_price', 16, 2)->nullable()->change();
        });

        Schema::table('ebooks', function (Blueprint $table) {
            $table->decimal('price', 16, 2)->default(0)->change();
            $table->decimal('original_price', 16, 2)->nullable()->change();
        });

        Schema::table('webinars', function (Blueprint $table) {
            $table->decimal('price', 16, 2)->default(0)->change();
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->decimal('amount_paid', 16, 2)->default(0)->change();
        });

        Schema::table('course_duration_tiers', function (Blueprint $table) {
            $table->decimal('price', 16, 2)->default(0)->change();
            $table->decimal('original_price', 16, 2)->nullable()->change();
        });

        Schema::table('course_payments', function (Blueprint $table) {
            $table->decimal('amount', 16, 2)->change();
            $table->decimal('transfer_amount', 16, 2)->nullable()->change();
            $table->decimal('discount_amount', 16, 2)->default(0)->change();
            $table->decimal('original_amount', 16, 2)->nullable()->change();
        });

        Schema::table('vouchers', function (Blueprint $table) {
            $table->decimal('discount_value', 16, 2)->change();
            $table->decimal('min_order_amount', 16, 2)->default(0)->change();
            $table->decimal('max_discount', 16, 2)->nullable()->change();
        });

        Schema::table('voucher_usages', function (Blueprint $table) {
            $table->decimal('discount_amount', 16, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('voucher_usages', function (Blueprint $table) {
            $table->decimal('discount_amount', 12, 2)->change();
        });

        Schema::table('vouchers', function (Blueprint $table) {
            $table->decimal('discount_value', 12, 2)->change();
            $table->decimal('min_order_amount', 12, 2)->default(0)->change();
            $table->decimal('max_discount', 12, 2)->nullable()->change();
        });

        Schema::table('course_payments', function (Blueprint $table) {
            $table->decimal('amount', 12, 2)->change();
            $table->decimal('transfer_amount', 12, 2)->nullable()->change();
            $table->decimal('discount_amount', 12, 2)->default(0)->change();
            $table->decimal('original_amount', 12, 2)->nullable()->change();
        });

        Schema::table('course_duration_tiers', function (Blueprint $table) {
            $table->decimal('price', 12, 2)->default(0)->change();
            $table->decimal('original_price', 12, 2)->nullable()->change();
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->decimal('amount_paid', 10, 2)->default(0)->change();
        });

        Schema::table('webinars', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->default(0)->change();
        });

        Schema::table('ebooks', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->default(0)->change();
            $table->decimal('original_price', 10, 2)->nullable()->change();
        });

        Schema::table('course_sections', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->default(0)->change();
            $table->decimal('original_price', 10, 2)->nullable()->change();
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->default(0)->change();
            $table->decimal('original_price', 10, 2)->nullable()->change();
        });
    }
};
