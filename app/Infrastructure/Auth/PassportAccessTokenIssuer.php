<?php

namespace App\Infrastructure\Auth;

use App\Domain\Auth\AccessToken;
use App\Domain\Auth\Service\AccessTokenIssuer;
use App\Domain\Auth\User;
use App\Infrastructure\Persistence\Eloquent\UserModel;
use DateTimeImmutable;

final class PassportAccessTokenIssuer implements AccessTokenIssuer
{
    public function issueFor(User $user): AccessToken
    {
        $result = UserModel::query()->findOrFail($user->id->value)->createToken('api');

        return new AccessToken(
            value: $result->accessToken,
            expiresAt: new DateTimeImmutable('+'.$result->expiresIn.' seconds'),
        );
    }
}
