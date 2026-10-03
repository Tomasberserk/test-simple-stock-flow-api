<?php

declare(strict_types=1);

use App\Presentation\Http\Controllers\AuthController;
use App\Presentation\Http\Controllers\CategoryController;
use App\Presentation\Http\Controllers\HealthController;
use App\Presentation\Http\Controllers\ProductController;
use App\Presentation\Http\Controllers\ReportController;
use App\Presentation\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Simple Stock Flow (Onion Architecture)
|--------------------------------------------------------------------------
*/

// Health Check
Route::get('/health', [HealthController::class, 'check']);

// 1. Autenticación
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    // DP-04: Solo administradores pueden registrar nuevos vendedores
    Route::middleware(['jwt.auth', 'role:admin'])->post('/register-seller', [AuthController::class, 'registerSeller']);
});

// 2. Categorías (Lectura pública para filtros y formularios)
Route::get('/categories', [CategoryController::class, 'index']);

// 3. Catálogo de Productos
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/{id}', [ProductController::class, 'show']);

    // Rutas protegidas solo para rol 'admin'
    Route::middleware(['jwt.auth', 'role:admin'])->group(function () {
        Route::post('/', [ProductController::class, 'store']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
    });
});

// 4. Ventas (Acceso para 'admin' y 'seller')
Route::prefix('sales')->middleware(['jwt.auth', 'role:admin,seller'])->group(function () {
    Route::post('/', [SaleController::class, 'store']);
    Route::get('/', [SaleController::class, 'index']);
    Route::get('/{id}', [SaleController::class, 'show']);
});

// 5. Reportes (Acceso exclusivo 'admin')
Route::prefix('reports')->middleware(['jwt.auth', 'role:admin'])->group(function () {
    Route::get('/sales', [ReportController::class, 'sales']);
});
