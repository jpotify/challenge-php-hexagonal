<?php

namespace App\Domain\Gif\Exception;

use App\Domain\Gif\GifId;
use RuntimeException;

final class GifNotFound extends RuntimeException
{
    public static function withId(GifId $id): self
    {
        return new self("The gif [{$id->value}] was not found.");
    }
}
