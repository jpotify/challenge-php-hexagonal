<?php

namespace Tests\Feature\Infrastructure\Console;

use App\Infrastructure\Persistence\Eloquent\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Client;
use Tests\TestCase;

class SetupApiCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_the_personal_access_client_once_so_users_can_log_in(): void
    {
        $this->artisan('api:setup')->assertSuccessful();
        $this->artisan('api:setup')->assertSuccessful();

        $this->assertSame(1, Client::query()->count());

        UserModel::factory()->create(['email' => 'demo@example.com', 'password' => 'secret-pass']);
        $this->postJson('/api/v1/auth/login', ['email' => 'demo@example.com', 'password' => 'secret-pass'])
            ->assertOk();
    }
}
