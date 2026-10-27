<?php

namespace Tests\Fakes;

use App\Domain\Auth\AccessToken;
use App\Domain\Auth\Service\AccessTokenIssuer;
use App\Domain\Auth\User;
use DateTimeImmutable;

final class FakeAccessTokenIssuer implements AccessTokenIssuer
{
    /** @var list<User> */
    public array $issuedFor = [];

    public function issueFor(User $user): AccessToken
    {
        $this->issuedFor[] = $user;

        return new AccessToken('token-for-'.$user->id->value, new DateTimeImmutable('+30 minutes'));
    }
}
