<?php

namespace App\Domain\Gif\Exception;

use RuntimeException;

final class FavoriteOwnerMismatch extends RuntimeException
{
    public static function create(): self
    {
        return new self('Favorites can only be saved for the authenticated user.');
    }
}
