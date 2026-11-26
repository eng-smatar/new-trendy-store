<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ValidateSignature;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Reset links are built from the request's host: accept only APP_URL's host and subdomains.
        // (Laravel skips this check locally and in tests.)
        $middleware->trustHosts();
        // Every signed route checks the path and parameters only, so a proxy changing the scheme or
        // host cannot break a link. Whoever generates one must sign it relative too
        // (App\Notifications\ResetPassword; same for email verification if it is ever enabled).
        $middleware->alias(['signed' => ValidateSignature::relative()]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
