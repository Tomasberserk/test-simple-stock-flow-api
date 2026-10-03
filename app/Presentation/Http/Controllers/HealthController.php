<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class HealthController
{
    public function check(): JsonResponse
    {
        $dbStatus = 'connected';
        try {
            DB::connection()->getPdo();
        } catch (Throwable $e) {
            $dbStatus = 'disconnected';
        }

        return response()->json([
            'status' => $dbStatus === 'connected' ? 'ok' : 'degraded',
            'service' => 'simple-stock-flow-api',
            'database' => $dbStatus,
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
        ], $dbStatus === 'connected' ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE);
    }
}
