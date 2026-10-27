<?php

namespace App\Infrastructure\Persistence\Eloquent\Repository;

use App\Domain\Auth\Repository\UserRepository;
use App\Domain\Auth\User;
use App\Domain\Shared\ValueObject\UserId;
use App\Infrastructure\Persistence\Eloquent\UserModel;

final class EloquentUserRepository implements UserRepository
{
    public function findByEmail(string $email): ?User
    {
        $model = UserModel::query()->where('email', $email)->first();

        if ($model === null) {
            return null;
        }

        return new User(
            id: new UserId($model->id),
            name: $model->name,
            email: $model->email,
            passwordHash: $model->password,
        );
    }
}
