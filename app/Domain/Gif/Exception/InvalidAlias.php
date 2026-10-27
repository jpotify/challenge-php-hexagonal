<?php

namespace App\Domain\Gif\Exception;

use App\Domain\Shared\Exception\InvalidInput;

final class InvalidAlias extends InvalidInput
{
    public static function withMaxLength(int $maxLength): self
    {
        return new self("The alias must contain between 1 and {$maxLength} characters.");
    }
}
