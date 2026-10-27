<?php

namespace Tests\Fakes;

use App\Domain\Auth\Service\PasswordVerifier;

/**
 * Treats the "hash" as the plain password prefixed with "hashed:".
 */
final class PrefixPasswordVerifier implements PasswordVerifier
{
    public static function hash(string $plainPassword): string
    {
        return 'hashed:'.$plainPassword;
    }

    public function verify(string $plainPassword, string $passwordHash): bool
    {
        return self::hash($plainPassword) === $passwordHash;
    }
}
