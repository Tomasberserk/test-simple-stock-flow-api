<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Ports\Outbound\UserRepository;
use App\Domain\Model\User;
use App\Domain\ValueObject\UserId;
use App\Domain\ValueObject\Username;
use App\Infrastructure\Persistence\Mappers\UserMapper;
use App\Infrastructure\Persistence\Models\UserModel;

final class EloquentUserRepository implements UserRepository
{
    public function findByUsername(Username $username): ?User
    {
        $model = UserModel::where('username', $username->getValue())->first();
        return $model !== null ? UserMapper::toDomain($model) : null;
    }

    public function findById(UserId $id): ?User
    {
        $model = UserModel::find($id->getValue());
        return $model !== null ? UserMapper::toDomain($model) : null;
    }

    public function save(User $user): void
    {
        UserModel::create([
            'id' => $user->getId()->getValue(),
            'username' => $user->getUsername()->getValue(),
            'password_hash' => $user->getPasswordHash(),
            'role' => $user->getRole()->getValue(),
        ]);
    }
}
