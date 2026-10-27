<?php

namespace App\Domain\Gif\Exception;

use App\Domain\Shared\Exception\InvalidInput;

final class InvalidSearchCriteria extends InvalidInput
{
    public static function because(string $reason): self
    {
        return new self($reason);
    }
}
