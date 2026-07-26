<?php

/**
 * ---------------------------------------------------------------------
 * SNIPPET — merge this into your existing bootstrap/app.php
 * (Laravel 11+ structure). This is NOT a standalone file to drop in;
 * it shows exactly what to add inside withExceptions() and
 * withMiddleware().
 * ---------------------------------------------------------------------
 */
 
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register Spatie's middleware aliases (role / permission / role_or_permission)
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
 
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Spatie throws its own UnauthorizedException (not Laravel's) when a
        // role/permission middleware check fails — catch it explicitly so
        // the SPA always gets a predictable { message, error } JSON shape
        // instead of a redirect-to-login HTML response.
        $exceptions->render(function (UnauthorizedException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'You do not have permission to perform this action.',
                    'error' => 'forbidden',
                ], 403);
            }
        });
 
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'This action is unauthorized.',
                    'error' => 'forbidden',
                ], 403);
            }
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
