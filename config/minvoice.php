<?php

return [
    'enabled' => env('MINVOICE_ENABLED', false),
    'base_url' => rtrim((string) env('MINVOICE_BASE_URL', ''), '/'),
    'username' => env('MINVOICE_USERNAME'),
    'password' => env('MINVOICE_PASSWORD'),
    'branch_code' => env('MINVOICE_BRANCH_CODE', 'VP'),
    'tax_code' => env('MINVOICE_TAX_CODE'),
    'invoice_type' => (int) env('MINVOICE_INVOICE_TYPE', 1),
    'default_invoice_series' => env('MINVOICE_DEFAULT_INVOICE_SERIES'),
    'currency_code' => env('MINVOICE_CURRENCY_CODE', 'VND'),
    'exchange_rate' => (float) env('MINVOICE_EXCHANGE_RATE', 1),
    'payment_method_name' => env('MINVOICE_PAYMENT_METHOD_NAME', 'Chuyển khoản'),
    'vat_rate' => (float) env('MINVOICE_VAT_RATE', 0),
    'vat_code' => env('MINVOICE_VAT_CODE', '0'),
    'unit_code' => env('MINVOICE_UNIT_CODE', 'Goi'),
    'item_code_prefix' => env('MINVOICE_ITEM_CODE_PREFIX', 'SONET'),
    'request_timeout' => (int) env('MINVOICE_REQUEST_TIMEOUT', 30),
    'token_cache_minutes' => (int) env('MINVOICE_TOKEN_CACHE_MINUTES', 50),
];