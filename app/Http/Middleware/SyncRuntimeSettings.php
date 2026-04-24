<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;

class SyncRuntimeSettings
{
    public function handle(Request $request, Closure $next)
    {
        config([
            'bunnycdn.api_key' => SettingsService::get('bunnycdn.api_key', config('bunnycdn.api_key')),
            'bunnycdn.storage_zone_name' => SettingsService::get('bunnycdn.storage_zone_name', config('bunnycdn.storage_zone_name')),
            'bunnycdn.pull_zone_url' => SettingsService::get('bunnycdn.pull_zone_url', config('bunnycdn.pull_zone_url')),
            'bunnycdn.video_library_id' => SettingsService::get('bunnycdn.video_library_id', config('bunnycdn.video_library_id')),
            'bunnycdn.video_api_key' => SettingsService::get('bunnycdn.video_api_key', config('bunnycdn.video_api_key')),
            'bunnycdn.stream_hostname' => SettingsService::get('bunnycdn.stream_hostname', config('bunnycdn.stream_hostname', 'iframe.mediadelivery.net')),
            'bunnycdn.token_auth_key' => SettingsService::get('bunnycdn.token_auth_key', config('bunnycdn.token_auth_key')),
            'bunnycdn.enable_token_auth' => SettingsService::get('bunnycdn.enable_token_auth', config('bunnycdn.enable_token_auth')),
            'bunnycdn.token_ttl' => SettingsService::get('bunnycdn.token_ttl', config('bunnycdn.token_ttl', 3600)),
            'sepay.webhook_token' => SettingsService::get('sepay.webhook_token', config('sepay.webhook_token')),
            'sepay.pattern' => SettingsService::get('sepay.pattern', config('sepay.pattern', 'SE')),
            'sepay_gateway.bank_code' => SettingsService::get('sepay_gateway.bank_code', config('sepay_gateway.bank_code')),
            'sepay_gateway.account_number' => SettingsService::get('sepay_gateway.account_number', config('sepay_gateway.account_number')),
            'sepay_gateway.account_name' => SettingsService::get('sepay_gateway.account_name', config('sepay_gateway.account_name')),
            'minvoice.enabled' => (bool) SettingsService::get('minvoice.enabled', config('minvoice.enabled', false)),
            'minvoice.base_url' => rtrim((string) SettingsService::get('minvoice.base_url', config('minvoice.base_url', '')), '/'),
            'minvoice.username' => SettingsService::get('minvoice.username', config('minvoice.username')),
            'minvoice.password' => SettingsService::get('minvoice.password', config('minvoice.password')),
            'minvoice.branch_code' => SettingsService::get('minvoice.branch_code', config('minvoice.branch_code', 'VP')),
            'minvoice.tax_code' => SettingsService::get('minvoice.tax_code', config('minvoice.tax_code')),
            'minvoice.invoice_type' => (int) SettingsService::get('minvoice.invoice_type', config('minvoice.invoice_type', 1)),
            'minvoice.default_invoice_series' => SettingsService::get('minvoice.default_invoice_series', config('minvoice.default_invoice_series')),
            'minvoice.currency_code' => SettingsService::get('minvoice.currency_code', config('minvoice.currency_code', 'VND')),
            'minvoice.exchange_rate' => (float) SettingsService::get('minvoice.exchange_rate', config('minvoice.exchange_rate', 1)),
            'minvoice.payment_method_name' => SettingsService::get('minvoice.payment_method_name', config('minvoice.payment_method_name', 'Chuyển khoản')),
            'minvoice.vat_rate' => (float) SettingsService::get('minvoice.vat_rate', config('minvoice.vat_rate', 0)),
            'minvoice.vat_code' => (string) SettingsService::get('minvoice.vat_code', config('minvoice.vat_code', '0')),
            'minvoice.unit_code' => SettingsService::get('minvoice.unit_code', config('minvoice.unit_code', 'Goi')),
            'minvoice.item_code_prefix' => SettingsService::get('minvoice.item_code_prefix', config('minvoice.item_code_prefix', 'SONET')),
            'minvoice.request_timeout' => (int) SettingsService::get('minvoice.request_timeout', config('minvoice.request_timeout', 30)),
            'minvoice.token_cache_minutes' => (int) SettingsService::get('minvoice.token_cache_minutes', config('minvoice.token_cache_minutes', 50)),
        ]);

        return $next($request);
    }
}
