<?php

namespace App\Application\Auth;

use App\Domain\Auth\AccessToken;
use App\Domain\Auth\Exception\InvalidCredentials;
use App\Domain\Auth\Repository\UserRepository;
use App\Domain\Auth\Service\AccessTokenIssuer;
use App\Domain\Auth\Service\PasswordVerifier;

final readonly class Login
{
    public function __construct(
        private UserRepository $users,
        private PasswordVerifier $passwordVerifier,
        private AccessTokenIssuer $tokenIssuer,
    ) {}

    /**
     * @throws InvalidCredentials
     */
    public function execute(LoginCommand $command): AccessToken
    {
        $user = $this->users->findByEmail($command->email);

        if ($user === null || ! $this->passwordVerifier->verify($command->password, $user->passwordHash)) {
            throw InvalidCredentials::create();
        }

        return $this->tokenIssuer->issueFor($user);
    }
}
