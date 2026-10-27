<?php

namespace App\Domain\Auth\Exception;

use RuntimeException;

final class InvalidCredentials extends RuntimeException
{
    public static function create(): self
    {
        return new self('The provided credentials are invalid.');
    }
}
