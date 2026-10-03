<?php

declare(strict_types=1);

namespace App\Application\Ports;

interface TransactionManagerInterface
{
    /**
     * Ejecuta una operación de forma atómica.
     * Desacopla la capa de aplicación de DB::transaction() de Laravel.
     */
    public function execute(callable $operation): mixed;
}
