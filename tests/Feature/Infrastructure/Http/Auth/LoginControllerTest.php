<?php

namespace Tests\Feature\Infrastructure\Http\Auth;

use App\Infrastructure\Persistence\Eloquent\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use InteractsWithApi, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpApi();
    }

    public function test_returns_200_with_a_bearer_token_that_expires_in_30_minutes(): void
    {
        UserModel::factory()->create(['email' => 'demo@example.com', 'password' => 'secret-pass']);

        $response = $this->postJson('/api/v1/auth/login', ['email' => 'demo@example.com', 'password' => 'secret-pass']);

        $response->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'expires_in', 'expires_at'])
            ->assertJsonPath('token_type', 'Bearer')
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->assertEqualsWithDelta(1800, $response->json('expires_in'), 5);
    }

    public function test_issued_token_authenticates_and_carries_a_30_minute_expiration(): void
    {
        Http::fake(['api.giphy.com/*' => Http::response([], 404)]);
        $user = UserModel::factory()->create(['password' => 'secret-pass']);
        $token = $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'secret-pass'])
            ->json('token');

        $this->withToken($token)->getJson('/api/v1/gifs/abc123')->assertNotFound();

        // Passport validates the JWT "exp" claim against the system clock, which tests cannot travel.
        $claims = json_decode((string) base64_decode(strtr(explode('.', $token)[1], '-_', '+/')), true);
        $this->assertEqualsWithDelta(time() + 1800, $claims['exp'], 5);
    }

    public function test_returns_401_for_a_wrong_password(): void
    {
        UserModel::factory()->create(['email' => 'demo@example.com', 'password' => 'secret-pass']);

        $this->postJson('/api/v1/auth/login', ['email' => 'demo@example.com', 'password' => 'wrong'])
            ->assertUnauthorized()
            ->assertJsonMissingPath('token');
    }

    public function test_returns_401_for_an_unknown_email(): void
    {
        $this->postJson('/api/v1/auth/login', ['email' => 'nobody@example.com', 'password' => 'whatever'])
            ->assertUnauthorized();
    }

    public function test_returns_422_when_email_and_password_are_missing(): void
    {
        $this->postJson('/api/v1/auth/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_returns_422_for_a_malformed_email(): void
    {
        $this->postJson('/api/v1/auth/login', ['email' => 'not-an-email', 'password' => 'x'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_returns_429_after_too_many_attempts(): void
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->postJson('/api/v1/auth/login', ['email' => 'nobody@example.com', 'password' => 'x'])
                ->assertUnauthorized();
        }

        $this->postJson('/api/v1/auth/login', ['email' => 'nobody@example.com', 'password' => 'x'])
            ->assertTooManyRequests();
    }
}
