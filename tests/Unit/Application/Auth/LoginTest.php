<?php

namespace Tests\Unit\Application\Auth;

use App\Application\Auth\Login;
use App\Application\Auth\LoginCommand;
use App\Domain\Auth\Exception\InvalidCredentials;
use App\Domain\Auth\User;
use App\Domain\Shared\ValueObject\UserId;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\FakeAccessTokenIssuer;
use Tests\Fakes\InMemoryUserRepository;
use Tests\Fakes\PrefixPasswordVerifier;

class LoginTest extends TestCase
{
    private FakeAccessTokenIssuer $issuer;

    private Login $login;

    protected function setUp(): void
    {
        $users = (new InMemoryUserRepository)->add(new User(
            new UserId(7),
            'Demo',
            'demo@example.com',
            PrefixPasswordVerifier::hash('secret-pass'),
        ));

        $this->issuer = new FakeAccessTokenIssuer;
        $this->login = new Login($users, new PrefixPasswordVerifier, $this->issuer);
    }

    public function test_returns_an_access_token_for_valid_credentials(): void
    {
        $token = $this->login->execute(new LoginCommand('demo@example.com', 'secret-pass'));

        $this->assertSame('token-for-7', $token->value);
        $this->assertCount(1, $this->issuer->issuedFor);
    }

    public function test_rejects_a_wrong_password_without_issuing_a_token(): void
    {
        $this->expectException(InvalidCredentials::class);

        try {
            $this->login->execute(new LoginCommand('demo@example.com', 'wrong'));
        } finally {
            $this->assertSame([], $this->issuer->issuedFor);
        }
    }

    public function test_rejects_an_unknown_email(): void
    {
        $this->expectException(InvalidCredentials::class);

        $this->login->execute(new LoginCommand('nobody@example.com', 'secret-pass'));
    }
}
