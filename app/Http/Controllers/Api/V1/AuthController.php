<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Register a new user.
     *
     * POST /api/v1/auth/register
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => $request->password,  // Auto-hashed via model cast
        ]);

        $token = JWTAuth::fromUser($user);

        return $this->created([
            'user'  => $this->formatUser($user),
            'token' => $this->tokenPayload($token),
        ], 'Registration successful.');
    }

    /**
     * Login and return JWT token.
     *
     * POST /api/v1/auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        $token = JWTAuth::attempt($credentials);

        if (!$token) {
            return $this->unauthorized('Invalid email or password.');
        }

        $user = auth()->user();

        return $this->success([
            'user'  => $this->formatUser($user),
            'token' => $this->tokenPayload($token),
        ], 'Login successful.');
    }

    /**
     * Get the authenticated user's profile.
     *
     * GET /api/v1/auth/me
     */
    public function me(): JsonResponse
    {
        $user = auth()->user();

        return $this->success(
            $this->formatUser($user),
            'Authenticated user retrieved.'
        );
    }

    /**
     * Logout (invalidate the current token).
     *
     * POST /api/v1/auth/logout
     */
    public function logout(): JsonResponse
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (\Throwable $e) {
            // Token may already be invalid — that's fine
        }

        return $this->success(null, 'Logged out successfully.');
    }

    /**
     * Refresh the JWT token.
     *
     * POST /api/v1/auth/refresh
     */
    public function refresh(): JsonResponse
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());
            $user     = auth()->user();

            return $this->success([
                'user'  => $this->formatUser($user),
                'token' => $this->tokenPayload($newToken),
            ], 'Token refreshed.');
        } catch (\Throwable $e) {
            return $this->unauthorized('Unable to refresh token. Please login again.');
        }
    }

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------

    /**
     * Build a standardized token payload.
     *
     * @return array<string, mixed>
     */
    private function tokenPayload(string $token): array
    {
        return [
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'expires_in'   => config('jwt.ttl') * 60,  // seconds
        ];
    }

    /**
     * Format user data for response.
     *
     * @return array<string, mixed>
     */
    private function formatUser($user): array
    {
        return [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
        ];
    }
}
