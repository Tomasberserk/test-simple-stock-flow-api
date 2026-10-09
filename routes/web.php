<?php

declare(strict_types=1);

use App\Presentation\Http\Controllers\HealthController;
use App\Presentation\Http\Controllers\MediaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Simple Stock Flow
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return response()->json(['service' => 'simple-stock-flow-api', 'status' => 'running']);
});

// E-14: GET /health (anónimo)
Route::get('/health', [HealthController::class, 'check']);

// E-15: GET /media/{key} (anónimo)
Route::get('/media/{key}', [MediaController::class, 'show']);
