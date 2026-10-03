<?php

declare(strict_types=1);

namespace App\Application\Ports\Outbound;

interface UnitOfWork
{
    /**
     * Ejecuta una unidad de trabajo de forma transaccional y atómica.
     * Desacopla la capa de aplicación de cualquier implementación técnica de transacciones.
     */
    public function execute(callable $operation): mixed;
}
