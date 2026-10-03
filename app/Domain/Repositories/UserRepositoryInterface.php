<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\User;

interface UserRepositoryInterface
{
    public function findByUsername(string $username): ?User;

    public function findById(string $id): ?User;

    public function save(User $user): void;
}
