<?php

namespace App\Domain\Shared\ValueObject;

use InvalidArgumentException;

final readonly class UserId
{
    public function __construct(public int $value)
    {
        if ($value < 1) {
            throw new InvalidArgumentException('User id must be a positive integer.');
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
