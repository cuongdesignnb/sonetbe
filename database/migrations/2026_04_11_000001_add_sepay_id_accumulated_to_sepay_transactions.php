<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sepay_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('sepay_id')->nullable()->after('id')->index();
            $table->bigInteger('accumulated')->nullable()->after('transferAmount');
        });
    }

    public function down(): void
    {
        Schema::table('sepay_transactions', function (Blueprint $table) {
            $table->dropColumn(['sepay_id', 'accumulated']);
        });
    }
};
