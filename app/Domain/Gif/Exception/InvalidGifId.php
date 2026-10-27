<?php

namespace App\Domain\Gif\Exception;

use App\Domain\Shared\Exception\InvalidInput;

final class InvalidGifId extends InvalidInput
{
    public static function forValue(string $value): self
    {
        return new self('The gif id must be an alphanumeric string of up to 64 characters.');
    }
}
