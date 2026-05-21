<?php

namespace App\Exceptions;

use App\Traits\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenBlacklistedException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ApiExceptionHandler extends ExceptionHandler
{
    use ApiResponse;

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        AuthenticationException::class,
        ValidationException::class,
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->renderable(function (Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return $this->handleApiException($e);
            }
        });
    }

    /**
     * Convert exceptions into standardized JSON responses.
     */
    protected function handleApiException(Throwable $e): JsonResponse
    {
        // ---- JWT-specific exceptions ----
        if ($e instanceof TokenExpiredException) {
            return $this->error('Token has expired. Please refresh your token.', 401);
        }

        if ($e instanceof TokenInvalidException) {
            return $this->error('Token is invalid.', 401);
        }

        if ($e instanceof TokenBlacklistedException) {
            return $this->error('Token has been blacklisted. Please login again.', 401);
        }

        if ($e instanceof JWTException) {
            return $this->error('Token error: ' . $e->getMessage(), 401);
        }

        // ---- Authentication ----
        if ($e instanceof AuthenticationException) {
            return $this->unauthorized('You are not authenticated. Please login.');
        }

        // ---- Validation ----
        if ($e instanceof ValidationException) {
            return $this->validationError(
                $e->errors(),
                'The given data was invalid.'
            );
        }

        // ---- Model not found ----
        if ($e instanceof ModelNotFoundException) {
            $model = class_basename($e->getModel());
            return $this->notFound("{$model} not found.");
        }

        // ---- Route not found ----
        if ($e instanceof NotFoundHttpException) {
            return $this->notFound('The requested endpoint does not exist.');
        }

        // ---- Method not allowed ----
        if ($e instanceof MethodNotAllowedHttpException) {
            return $this->error(
                'The HTTP method is not allowed for this endpoint.',
                405
            );
        }

        // ---- Other HTTP exceptions ----
        if ($e instanceof HttpException) {
            return $this->error(
                $e->getMessage() ?: 'HTTP error',
                $e->getStatusCode()
            );
        }

        // ---- Catch-all: server error ----
        $message = config('app.debug')
            ? $e->getMessage()
            : 'An unexpected error occurred. Please try again later.';

        return $this->serverError($message);
    }
}
