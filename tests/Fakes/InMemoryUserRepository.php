<?php

namespace Tests\Fakes;

use App\Domain\Auth\Repository\UserRepository;
use App\Domain\Auth\User;

final class InMemoryUserRepository implements UserRepository
{
    /** @var array<string, User> */
    private array $users = [];

    public function add(User $user): self
    {
        $this->users[$user->email] = $user;

        return $this;
    }

    public function findByEmail(string $email): ?User
    {
        return $this->users[$email] ?? null;
    }
}
