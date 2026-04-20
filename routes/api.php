<?php

use App\Http\Controllers\ApiAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [ApiAuthController::class, 'login'])->name('api.login');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/dashboard/stats', [DashboardController::class, 'index']);
    Route::post('users/deactivate-inactive', [UserController::class, 'deactivateInactive']);

    Route::middleware('tenant.extract')->group(function (): void {
        Route::post('/products', [ProductController::class, 'store']);
        Route::post('imports/products', [ProductController::class, 'import']);
        Route::get('/projects', [ProjectController::class, 'index']);
        Route::middleware('trottle:5,1')->get('products/{product}/image', [ProductController::class, 'image']);
        Route::post('/api/imports/products', [ProductController::class, 'import']);
    });
});
