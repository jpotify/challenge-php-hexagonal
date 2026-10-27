<?php

namespace App\Domain\Auth\Repository;

use App\Domain\Auth\User;

interface UserRepository
{
    public function findByEmail(string $email): ?User;
}
