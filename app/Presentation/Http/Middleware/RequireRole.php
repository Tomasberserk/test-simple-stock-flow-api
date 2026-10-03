<?php

declare(strict_types=1);

namespace App\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireRole
{
    /**
     * @param string ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $currentRole = $request->attributes->get('auth_role');

        if ($currentRole === null || !in_array($currentRole, $roles, true)) {
            return response()->json([
                'error' => 'Acceso denegado. Permisos insuficientes'
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
