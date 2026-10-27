<?php

namespace App\Domain\Gif\Exception;

use App\Domain\Gif\GifId;
use RuntimeException;

final class FavoriteAlreadyExists extends RuntimeException
{
    public static function forGif(GifId $gifId): self
    {
        return new self("The gif [{$gifId->value}] is already one of the user's favorites.");
    }
}
