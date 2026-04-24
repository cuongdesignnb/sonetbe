<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_requests', function (Blueprint $table) {
            $table->string('provider', 50)->nullable()->after('status');
            $table->string('provider_invoice_id')->nullable()->after('provider');
            $table->string('provider_invoice_key')->nullable()->after('provider_invoice_id');
            $table->string('invoice_number', 50)->nullable()->after('provider_invoice_key');
            $table->string('invoice_series', 20)->nullable()->after('invoice_number');
            $table->string('provider_status', 100)->nullable()->after('invoice_series');
            $table->json('provider_request_payload')->nullable()->after('provider_status');
            $table->json('provider_response_payload')->nullable()->after('provider_request_payload');
            $table->text('last_error')->nullable()->after('provider_response_payload');
            $table->unsignedInteger('retry_count')->default(0)->after('last_error');
            $table->timestamp('processing_started_at')->nullable()->after('retry_count');
            $table->timestamp('processing_completed_at')->nullable()->after('processing_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_requests', function (Blueprint $table) {
            $table->dropColumn([
                'provider',
                'provider_invoice_id',
                'provider_invoice_key',
                'invoice_number',
                'invoice_series',
                'provider_status',
                'provider_request_payload',
                'provider_response_payload',
                'last_error',
                'retry_count',
                'processing_started_at',
                'processing_completed_at',
            ]);
        });
    }
};