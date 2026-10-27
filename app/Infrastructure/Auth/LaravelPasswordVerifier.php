<?php

namespace App\Infrastructure\Auth;

use App\Domain\Auth\Service\PasswordVerifier;
use Illuminate\Support\Facades\Hash;

final class LaravelPasswordVerifier implements PasswordVerifier
{
    public function verify(string $plainPassword, string $passwordHash): bool
    {
        return Hash::check($plainPassword, $passwordHash);
    }
}
