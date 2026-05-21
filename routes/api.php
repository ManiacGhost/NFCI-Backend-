<?php

use App\Http\Controllers\Api\V1\AssetController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ComponentTypeController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\PublicPageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — /api/v1
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api by Laravel's default API routing.
| We add a v1/ prefix group for API versioning.
|
*/

Route::prefix('v1')->group(function () {

    // ==========================================================
    // Public (no auth required)
    // ==========================================================

    // Auth: register & login
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login',    [AuthController::class, 'login']);
    });

    // Public page rendering (for the frontend)
    Route::get('/render/{pageNumber}', [PublicPageController::class, 'render'])
        ->where('pageNumber', '[0-9]+');

    // ==========================================================
    // Protected (JWT auth required)
    // ==========================================================

    Route::middleware('auth:api')->group(function () {

        // --- Auth management ---
        Route::prefix('auth')->group(function () {
            Route::post('/logout',  [AuthController::class, 'logout']);
            Route::post('/refresh', [AuthController::class, 'refresh']);
            Route::get('/me',       [AuthController::class, 'me']);
        });

        // --- Pages CRUD ---
        Route::prefix('pages')->group(function () {
            Route::get('/',             [PageController::class, 'index']);
            Route::get('/{pageNumber}', [PageController::class, 'show'])
                ->where('pageNumber', '[0-9]+');
            Route::post('/',            [PageController::class, 'store']);
            Route::put('/{pageNumber}', [PageController::class, 'update'])
                ->where('pageNumber', '[0-9]+');
            Route::delete('/{pageNumber}', [PageController::class, 'destroy'])
                ->where('pageNumber', '[0-9]+');

            // --- Page Components ---
            Route::prefix('{pageNumber}/components')->where(['pageNumber' => '[0-9]+'])->group(function () {
                Route::post('/',                       [PageController::class, 'attachComponents']);
                Route::put('/{pageComponentId}',       [PageController::class, 'updateComponent']);
                Route::delete('/{pageComponentId}',    [PageController::class, 'removeComponent']);
                Route::patch('/reorder',               [PageController::class, 'reorderComponents']);
            });
        });

        // --- Component Types & Variants ---
        Route::prefix('component-types')->group(function () {
            Route::get('/',                   [ComponentTypeController::class, 'index']);
            Route::post('/',                  [ComponentTypeController::class, 'store']);
            Route::post('/{code}/variants',   [ComponentTypeController::class, 'storeVariant']);
        });

        // --- Assets ---
        Route::prefix('assets')->group(function () {
            Route::post('/upload',       [AssetController::class, 'upload']);
            Route::delete('/{assetId}',  [AssetController::class, 'destroy']);
        });
    });
});
