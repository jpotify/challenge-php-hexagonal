<?php

namespace App\Domain\Auth;

use App\Domain\Shared\ValueObject\UserId;

final readonly class User
{
    public function __construct(
        public UserId $id,
        public string $name,
        public string $email,
        public string $passwordHash,
    ) {}
}
