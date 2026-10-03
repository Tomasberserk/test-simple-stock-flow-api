<?php

declare(strict_types=1);

namespace App\Application\Ports\Outbound;

interface FileStorage
{
    /**
     * Guarda el contenido de la imagen y retorna una clave opaca única (D-08).
     * El dominio nunca ve una ruta de disco ni URL.
     */
    public function store(string $content, string $extension): string;

    public function delete(string $key): void;

    public function getUrl(string $key): ?string;
}
