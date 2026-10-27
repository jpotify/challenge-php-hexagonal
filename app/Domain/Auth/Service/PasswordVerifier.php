<?php

namespace App\Domain\Auth\Service;

interface PasswordVerifier
{
    public function verify(string $plainPassword, string $passwordHash): bool;
}
