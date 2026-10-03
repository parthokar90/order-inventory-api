<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\OrderController;

Route::prefix('v1')->group(function () {

    // Public Auth Routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);

    Route::apiResource('products', ProductController::class)->only(['index', 'show']);

    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::middleware(['throttle:10,1', 'idempotency'])->group(function () {
            Route::post('/orders', [OrderController::class, 'store']);
        });

        // Admin-only 
        Route::middleware(['role:admin'])->group(function () {
            Route::apiResource('categories', CategoryController::class)->only(['store', 'update', 'destroy']);
            Route::apiResource('products', ProductController::class)->only(['store', 'update', 'destroy']);

            Route::prefix('inventories')->group(function () {
                Route::get('/', [InventoryController::class, 'index']); 
            });

            Route::patch('/orders/{id}/status', [OrderController::class, 'updateStatus'])
                ->middleware('throttle:30,1');

            Route::post('/orders/{id}/payment-callback', [OrderController::class, 'handlePaymentCallback']);
        });

        // Customer-only testing route
        Route::middleware(['role:customer'])->prefix('customer')->group(function () {
            Route::get('/my-orders', function () {
                return response()->json(['message' => 'Your order history']);
            });
        });
    });
});
