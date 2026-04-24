<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Using stateless token auth, no need for EnsureFrontendRequestsAreStateful

        $middleware->alias([
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'block.download.browsers' => \App\Http\Middleware\BlockDownloadBrowsers::class,
        ]);

        // CORS configuration
        $middleware->web(append: [
            \App\Http\Middleware\HandleCors::class,
            \App\Http\Middleware\SyncRuntimeSettings::class,
        ]);
        
        $middleware->api(append: [
            \App\Http\Middleware\HandleCors::class,
            \App\Http\Middleware\SyncRuntimeSettings::class,
        ]);

        // Return JSON 401 for unauthenticated API requests instead of redirecting to login
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                abort(response()->json(['message' => 'Unauthenticated'], 401));
            }
            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();