<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vouchers can now target individual chapters (applicable_type =
     * "sections"): they only apply when that chapter is bought on its own.
     */
    public function up(): void
    {
        // enum('all','specific') cannot hold the new value; a short string can.
        Schema::table('vouchers', function (Blueprint $table) {
            $table->string('applicable_type', 20)->default('all')->change();
        });

        Schema::create('voucher_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('vouchers')->onDelete('cascade');
            $table->foreignId('course_section_id')->constrained('course_sections')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['voucher_id', 'course_section_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_sections');
        // applicable_type stays a string: converting back to the enum would
        // fail for vouchers that use "sections".
    }
};
