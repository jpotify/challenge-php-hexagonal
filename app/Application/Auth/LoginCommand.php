<?php

namespace App\Application\Auth;

final readonly class LoginCommand
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}
