<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // AuthenticateWithCookie middleware removed - using Sanctum session-based auth
        // Add EnsureFrontendRequestsAreStateful for Sanctum cookie-based authentication
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                $status = 500;
                $messages = ['An error occurred. Please try again.'];

                if ($e instanceof AuthenticationException) {
                    $status = 401;
                    $messages = ['Unauthenticated. Please login to continue.'];
                } elseif ($e instanceof ValidationException) {
                    $status = 422;
                    $messages = collect($e->errors())->flatten()->toArray();
                } elseif ($e instanceof NotFoundHttpException) {
                    $status = 404;
                    $messages = ['Resource not found.'];
                } elseif ($e instanceof HttpException) {
                    $status = $e->getStatusCode();
                    $messages = [$e->getMessage() ?: 'An error occurred.'];
                } elseif (method_exists($e, 'getStatusCode')) {
                    $status = $e->getStatusCode();
                    $messages = [$e->getMessage()];
                }

                return response()->json([
                    'status' => false,
                    'data' => null,
                    'messages' => $messages,
                    'code' => $status,
                ], $status);
            }
        });
    })->create();
