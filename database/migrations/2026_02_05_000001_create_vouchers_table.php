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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->enum('discount_type', ['fixed', 'percent'])->default('fixed');
            $table->decimal('discount_value', 12, 2);
            $table->decimal('min_order_amount', 12, 2)->default(0);
            $table->decimal('max_discount', 12, 2)->nullable(); // For percent type
            $table->integer('usage_limit')->nullable(); // null = unlimited
            $table->integer('used_count')->default(0);
            $table->integer('usage_per_user')->nullable(); // null = unlimited per user
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->enum('status', ['active', 'inactive', 'expired'])->default('active');
            $table->timestamps();

            $table->index(['code', 'status']);
            $table->index('valid_from');
            $table->index('valid_until');
        });

        // Track voucher usage per user
        Schema::create('voucher_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('vouchers')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('course_payment_id')->nullable()->constrained('course_payments')->onDelete('set null');
            $table->decimal('discount_amount', 12, 2);
            $table->timestamps();

            $table->index(['voucher_id', 'user_id']);
        });

        // Add voucher_id to course_payments
        Schema::table('course_payments', function (Blueprint $table) {
            $table->foreignId('voucher_id')->nullable()->after('order_code')->constrained('vouchers')->onDelete('set null');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('voucher_id');
            $table->decimal('original_amount', 12, 2)->nullable()->after('discount_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voucher_id');
            $table->dropColumn(['discount_amount', 'original_amount']);
        });

        Schema::dropIfExists('voucher_usages');
        Schema::dropIfExists('vouchers');
    }
};
