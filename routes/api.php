<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\InventoryController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\ReportController;
use App\Http\Controllers\API\SaleController;
use App\Http\Controllers\API\SystemSettingController;
use App\Http\Controllers\API\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Authentication (Public)
Route::post('/login', [AuthController::class, 'login']);

// Authentication (Protected)
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Dashboard (All Roles)
    Route::get('/dashboard', DashboardController::class);

    // Categories
    // View: All authenticated roles
    // Create, Update, Delete: Admin and Manager
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);

    Route::middleware('role:admin,manager')->group(function () {
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
    });

    // Products
    // View: All authenticated roles
    // Create, Update, Delete: Admin and Manager
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);

    Route::middleware('role:admin,manager')->group(function () {
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{id}', [ProductController::class, 'update']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    });

    // Sales (All Roles)
    Route::get('/sales', [SaleController::class, 'index']);
    Route::get('/sales/{id}', [SaleController::class, 'show']);
    Route::post('/sales', [SaleController::class, 'store']);

    // Void Sale (Admin and Manager)
    Route::post('/sales/{id}/void', [SaleController::class, 'void'])
        ->middleware('role:admin,manager');

    // Inventory (Admin and Manager)
    Route::middleware('role:admin,manager')->group(function () {
        Route::get('/inventory', [InventoryController::class, 'index']);
        Route::get('/inventory/movements', [InventoryController::class, 'movements']);
        Route::post('/inventory/adjust', [InventoryController::class, 'adjust']);
    });

    // Payments (All Roles)
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::get('/payments/receipt/{saleId}', [PaymentController::class, 'receipt']);

    // Reports (Admin and Manager)
    Route::middleware('role:admin,manager')->prefix('reports')->group(function () {
        Route::get('/daily-sales', [ReportController::class, 'dailySales']);
        Route::get('/revenue', [ReportController::class, 'revenue']);
        Route::get('/top-products', [ReportController::class, 'topProducts']);
        Route::get('/cashier-sales', [ReportController::class, 'cashierSales']);
    });

    // Users (Admin Only)
    Route::middleware('role:admin')->prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });

    // System Settings (Admin Only)
    Route::middleware('role:admin')->group(function () {
        Route::get('/settings', [SystemSettingController::class, 'index']);
        Route::put('/settings', [SystemSettingController::class, 'update']);
    });

});
