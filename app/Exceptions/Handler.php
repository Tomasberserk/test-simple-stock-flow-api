<?php

namespace App\Exceptions;

use App\Application\Exception\ConcurrencyConflict;
use App\Domain\Exception\BusinessRuleViolation;
use App\Domain\Exception\DuplicateUsernameException;
use App\Domain\Exception\InvalidCredentialsException;
use App\Domain\Exception\ProductNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     * Bloque 6 de ARQUITECTURA-ONION.md: Los 3 cuerpos de error no negociables.
     */
    public function register(): void
    {
        // 1. 401: Autenticación / Credenciales inválidas -> Cuerpo vacío
        $this->renderable(function (InvalidCredentialsException $e) {
            return response('', 401, [
                'Content-Length' => '0',
                'WWW-Authenticate' => 'Bearer',
            ]);
        });

        // 2. 404: No encontrado -> Cuerpo vacío
        $this->renderable(function (ProductNotFoundException $e) {
            return response('', 404, ['Content-Length' => '0']);
        });

        $this->renderable(function (NotFoundHttpException $e) {
            return response('', 404, ['Content-Length' => '0']);
        });

        // 3. 405: Método no permitido -> Cuerpo vacío con encabezado Allow
        $this->renderable(function (MethodNotAllowedHttpException $e) {
            return response('', 405, array_merge(['Content-Length' => '0'], $e->getHeaders()));
        });

        // 4. 409: Conflicto de concurrencia y unicidad -> application/problem+json con detail en español
        $this->renderable(function (ConcurrencyConflict $e) {
            return response()->json([
                'type' => 'about:blank',
                'title' => 'Conflicto de concurrencia',
                'status' => 409,
                'detail' => $e->getMessage(),
            ], 409, ['Content-Type' => 'application/problem+json']);
        });

        $this->renderable(function (DuplicateUsernameException $e) {
            return response()->json([
                'type' => 'about:blank',
                'title' => 'Conflicto de duplicidad',
                'status' => 409,
                'detail' => $e->getMessage(),
            ], 409, ['Content-Type' => 'application/problem+json']);
        });

        // 5. 422: Violación de regla de negocio -> application/problem+json con detail en español
        $this->renderable(function (BusinessRuleViolation $e) {
            return response()->json([
                'type' => 'about:blank',
                'title' => 'Regla de negocio no procesable',
                'status' => 422,
                'detail' => $e->getMessage(),
            ], 422, ['Content-Type' => 'application/problem+json']);
        });

        // 6. 400: Error de validación de forma / formato mal formado
        $this->renderable(function (ValidationException $e) {
            return response()->json([
                'title' => 'Formato inválido',
                'status' => 400,
                'detail' => $e->validator->errors()->first(),
                'errors' => $e->validator->errors()->toArray(),
            ], 400);
        });
    }
}
