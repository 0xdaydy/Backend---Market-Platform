<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // Prevent redirect to missing login route for API-only app
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Sanitize 404 messages — strip model class names
        $exceptions->render(function (ModelNotFoundException $e, Request $request): void {
            if ($request->is('api/*') || $request->expectsJson()) {
                abort(404, 'Resource not found.');
            }
        });

        // Ensure auth errors always return clean JSON for API routes
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Authentication required.'], 401);
            }
        });

        // Ensure authorization errors always return clean JSON for API routes
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'You do not have permission to perform this action.'], 403);
            }
        });

        // Log all exceptions with request context
        $exceptions->report(function (Throwable $e) {
            if ($e instanceof AuthenticationException || $e instanceof AuthorizationException) {
                return;
            }
            $request = request();
            Log::error($e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => $request?->fullUrl(),
                'method' => $request?->method(),
                'ip' => $request?->ip(),
            ]);
        });
    })->create();
