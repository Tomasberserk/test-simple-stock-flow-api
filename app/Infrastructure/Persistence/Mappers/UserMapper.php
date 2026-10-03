<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Mappers;

use App\Domain\Model\User;
use App\Domain\ValueObject\Role;
use App\Domain\ValueObject\UserId;
use App\Domain\ValueObject\Username;
use App\Infrastructure\Persistence\Models\UserModel;

final class UserMapper
{
    public static function toDomain(UserModel $model): User
    {
        return new User(
            id: UserId::fromString((string) $model->id),
            username: Username::fromString((string) $model->username),
            passwordHash: (string) $model->password_hash,
            role: new Role((string) $model->role)
        );
    }
}
