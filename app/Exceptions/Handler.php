<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
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
     */
    public function register(): void
    {
        $this->renderable(function (\App\Domain\Exception\InvalidCredentialsException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 401);
        });

        $this->renderable(function (\App\Domain\Exception\ProductNotFoundException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 404);
        });

        $this->renderable(function (\App\Application\Exception\ConcurrencyConflict $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 409);
        });

        $this->renderable(function (\App\Domain\Exception\DuplicateUsernameException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 409);
        });

        $this->renderable(function (\App\Domain\Exception\BusinessRuleViolation $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 422);
        });

        $this->renderable(function (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => $e->validator->errors()->first(),
                'errors' => $e->validator->errors()->toArray()
            ], 422);
        });
    }
}
