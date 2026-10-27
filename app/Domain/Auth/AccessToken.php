<?php

namespace App\Domain\Auth;

use DateTimeImmutable;

final readonly class AccessToken
{
    public function __construct(
        public string $value,
        public DateTimeImmutable $expiresAt,
    ) {}
}
