<?php

namespace App\Domain\Auth\Service;

use App\Domain\Auth\AccessToken;
use App\Domain\Auth\User;

interface AccessTokenIssuer
{
    public function issueFor(User $user): AccessToken;
}
