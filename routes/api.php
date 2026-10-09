<?php

declare(strict_types=1);

use App\Presentation\Http\Controllers\AuthController;
use App\Presentation\Http\Controllers\CategoryController;
use App\Presentation\Http\Controllers\HealthController;
use App\Presentation\Http\Controllers\MediaController;
use App\Presentation\Http\Controllers\ProductController;
use App\Presentation\Http\Controllers\ReportController;
use App\Presentation\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Simple Stock Flow (Onion Architecture)
| 15 endpoints oficiales según ARQUITECTURA-ONION.md y api-contract.md
|--------------------------------------------------------------------------
*/

// E-14: Health Check (anónimo)
Route::get('/health', [HealthController::class, 'check']);

// E-15: Media / Imágenes (anónimo)
Route::get('/media/{key}', [MediaController::class, 'show']);

// 1. Autenticación (Authenticate)
Route::prefix('auth')->group(function () {
    // E-01: Login (anónimo)
    Route::post('/login', [AuthController::class, 'login']);

    // E-02: Alta de usuario/vendedor (requiere admin, DP-04)
    Route::middleware(['jwt.auth', 'role:admin'])->group(function () {
        Route::post('/register', [AuthController::class, 'registerSeller']);
        Route::post('/register-seller', [AuthController::class, 'registerSeller']); // Alias
    });
});

// 2. Categorías (ManageProducts)
// E-09: GET /api/categories
Route::get('/categories', [CategoryController::class, 'index']);

// 3. Catálogo de Productos (ManageProducts)
Route::prefix('products')->group(function () {
    // E-03: Listar productos
    Route::get('/', [ProductController::class, 'index']);
    // E-04: Detalle de producto
    Route::get('/{id}', [ProductController::class, 'show']);

    // Rutas protegidas solo para rol 'admin'
    Route::middleware(['jwt.auth', 'role:admin'])->group(function () {
        // E-05: Crear producto
        Route::post('/', [ProductController::class, 'store']);
        // E-06: Actualizar producto
        Route::put('/{id}', [ProductController::class, 'update']);
        // E-07: Borrado lógico de producto
        Route::delete('/{id}', [ProductController::class, 'destroy']);
        // E-08: Subir imagen de producto
        Route::post('/{id}/image', [ProductController::class, 'uploadImage']);
    });
});

// 4. Ventas (PlaceSale / GetSales)
Route::prefix('sales')->middleware(['jwt.auth', 'role:admin,seller'])->group(function () {
    // E-10: Registrar venta atómica
    Route::post('/', [SaleController::class, 'store']);
    // E-11: Listar ventas
    Route::get('/', [SaleController::class, 'index']);
    // E-12: Detalle de venta
    Route::get('/{id}', [SaleController::class, 'show']);
});

// 5. Reportes (GetSalesReport)
Route::prefix('reports')->middleware(['jwt.auth', 'role:admin'])->group(function () {
    // E-13: Reporte consolidado de ventas
    Route::get('/sales', [ReportController::class, 'sales']);
});
