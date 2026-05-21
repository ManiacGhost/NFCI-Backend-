<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Standardized API response formatting.
 *
 * Every JSON response follows a consistent envelope:
 * {
 *   "success": bool,
 *   "message": string,
 *   "data":    mixed,
 *   "errors":  object|null,
 *   "meta":    object|null
 * }
 */
trait ApiResponse
{
    /**
     * Success response.
     */
    protected function success(
        mixed  $data    = null,
        string $message = 'Success',
        int    $code    = 200,
        array  $meta    = []
    ): JsonResponse {
        $payload = [
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ];

        if (!empty($meta)) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $code);
    }

    /**
     * Created response (201).
     */
    protected function created(
        mixed  $data    = null,
        string $message = 'Resource created successfully'
    ): JsonResponse {
        return $this->success($data, $message, 201);
    }

    /**
     * Error response.
     */
    protected function error(
        string $message = 'An error occurred',
        int    $code    = 400,
        mixed  $errors  = null
    ): JsonResponse {
        $payload = [
            'success' => false,
            'message' => $message,
            'data'    => null,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $code);
    }

    /**
     * Not found response (404).
     */
    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, 404);
    }

    /**
     * Unauthorized response (401).
     */
    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, 401);
    }

    /**
     * Forbidden response (403).
     */
    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, 403);
    }

    /**
     * Validation error response (422).
     */
    protected function validationError(mixed $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->error($message, 422, $errors);
    }

    /**
     * Server error response (500).
     */
    protected function serverError(string $message = 'Internal server error'): JsonResponse
    {
        return $this->error($message, 500);
    }
}
