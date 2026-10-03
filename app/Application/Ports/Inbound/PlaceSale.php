<?php

declare(strict_types=1);

namespace App\Application\Ports\Inbound;

interface PlaceSale
{
    public function execute(PlaceSaleCommand $command): SaleView;
}
