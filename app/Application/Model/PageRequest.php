<?php

declare(strict_types=1);

namespace App\Application\Model;

final class PageRequest
{
    public const MAX_PER_PAGE = 100;
    public const DEFAULT_PER_PAGE = 20;

    public readonly int $page;
    public readonly int $perPage;

    public function __construct(int $page = 1, int $perPage = self::DEFAULT_PER_PAGE)
    {
        $this->page = max(1, $page);
        // CA-01.5: Si perPage supera el máximo (100), aplica el máximo en lugar de rechazar
        $this->perPage = min(max(1, $perPage), self::MAX_PER_PAGE);
    }
}
