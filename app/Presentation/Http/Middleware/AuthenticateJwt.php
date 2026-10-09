<?php

declare(strict_types=1);

namespace App\Presentation\Http\Middleware;

use App\Infrastructure\Security\JwtTokenGenerator;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateJwt
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization');
        if ($header === null || !str_starts_with($header, 'Bearer ')) {
            return response('', Response::HTTP_UNAUTHORIZED, [
                'WWW-Authenticate' => 'Bearer',
                'Content-Length' => '0',
            ]);
        }

        $token = substr($header, 7);
        $payload = JwtTokenGenerator::decodeJwt($token);

        if ($payload === null) {
            return response('', Response::HTTP_UNAUTHORIZED, [
                'WWW-Authenticate' => 'Bearer error="invalid_token"',
                'Content-Length' => '0',
            ]);
        }

        // Adjuntar datos del usuario a la petición
        $request->attributes->set('auth_user_id', $payload['sub']);
        $request->attributes->set('auth_username', $payload['username']);
        $request->attributes->set('auth_role', $payload['role']);

        return $next($request);
    }
}
