<?php

declare(strict_types=1);

namespace App\Application\Ports\Outbound;

use App\Application\Ports\Inbound\AuthResult;
use App\Domain\ValueObject\Role;
use App\Domain\ValueObject\UserId;
use App\Domain\ValueObject\Username;

interface TokenGenerator
{
    public function generate(UserId $id, Username $username, Role $role): AuthResult;
}
