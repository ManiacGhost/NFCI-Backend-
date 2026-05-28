<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenBlacklistedException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Ensure API requests get the correct auth guard
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // ---- JSON error responses for all API requests ----

        $exceptions->render(function (TokenExpiredException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token has expired. Please refresh your token.',
                    'data'    => null,
                ], 401);
            }
        });

        $exceptions->render(function (TokenInvalidException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token is invalid.',
                    'data'    => null,
                ], 401);
            }
        });

        $exceptions->render(function (TokenBlacklistedException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token has been blacklisted. Please login again.',
                    'data'    => null,
                ], 401);
            }
        });

        $exceptions->render(function (JWTException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token error: ' . $e->getMessage(),
                    'data'    => null,
                ], 401);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authenticated. Please login.',
                    'data'    => null,
                ], 401);
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The given data was invalid.',
                    'data'    => null,
                    'errors'  => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $model = class_basename($e->getModel());
                return response()->json([
                    'success' => false,
                    'message' => "{$model} not found.",
                    'data'    => null,
                ], 404);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The requested endpoint does not exist.',
                    'data'    => null,
                ], 404);
            }
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The HTTP method is not allowed for this endpoint.',
                    'data'    => null,
                ], 405);
            }
        });

        // ---- Catch-all: log everything and return structured JSON ----
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $errorId = strtoupper(substr(md5(uniqid('', true)), 0, 8));

                $context = [
                    'error_id'  => $errorId,
                    'exception' => get_class($e),
                    'message'   => $e->getMessage(),
                    'code'      => $e->getCode(),
                    'file'      => $e->getFile(),
                    'line'      => $e->getLine(),
                    'trace'     => $e->getTraceAsString(),
                    'url'       => $request->fullUrl(),
                    'method'    => $request->method(),
                    'ip'        => $request->ip(),
                    'input'     => $request->except(['password', 'password_confirmation', 'token']),
                    'headers'   => $request->headers->all(),
                ];

                // Log to default channel
                \Illuminate\Support\Facades\Log::error("API Error [{$errorId}]: {$e->getMessage()}", $context);

                // Also log to dedicated api_errors channel
                try {
                    \Illuminate\Support\Facades\Log::channel('api_errors')
                        ->error("API Error [{$errorId}]: {$e->getMessage()}", $context);
                } catch (\Throwable $logEx) {
                    // Silently ignore if channel is unavailable
                }

                $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

                $response = [
                    'success'  => false,
                    'message'  => 'An unexpected error occurred. Please try again later.',
                    'data'     => null,
                    'error_id' => $errorId,
                ];

                if (config('app.debug')) {
                    $response['debug'] = [
                        'exception' => get_class($e),
                        'message'   => $e->getMessage(),
                        'file'      => $e->getFile(),
                        'line'      => $e->getLine(),
                        'trace'     => collect($e->getTrace())->take(10)->toArray(),
                    ];
                }

                return response()->json($response, $status);
            }
        });

    })->create();
