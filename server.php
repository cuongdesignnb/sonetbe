<?php

/**
 * Laravel's built-in development server router.
 *
 * When you run `php artisan serve`, Laravel starts PHP's built-in server
 * and uses this file to route requests to `public/index.php`.
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// This file allows PHP's built-in server to serve static assets directly.
if ($uri !== '/' && is_file(__DIR__ . '/public' . $uri)) {
    return false;
}

require_once __DIR__ . '/public/index.php';
