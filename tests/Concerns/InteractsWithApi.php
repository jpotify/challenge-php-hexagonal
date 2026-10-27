<?php

namespace Tests\Concerns;

use App\Infrastructure\Persistence\Eloquent\UserModel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\ClientRepository;

trait InteractsWithApi
{
    /**
     * Prepares Passport (keys + personal access client), a fake GIPHY key and blocks real HTTP calls.
     */
    protected function setUpApi(): void
    {
        if (! file_exists(storage_path('oauth-private.key')) || ! file_exists(storage_path('oauth-public.key'))) {
            Artisan::call('passport:keys', ['--force' => true]);
        }

        app(ClientRepository::class)->createPersonalAccessGrantClient('tests', 'users');

        config(['services.giphy.key' => 'test-key']);

        Http::preventStrayRequests();
    }

    protected function bearerTokenFor(UserModel $user): string
    {
        return $user->createToken('tests')->accessToken;
    }

    /**
     * @return array<string, string>
     */
    protected function authHeadersFor(UserModel $user): array
    {
        return ['Authorization' => 'Bearer '.$this->bearerTokenFor($user)];
    }

    protected function giphyFixture(string $name): string
    {
        return (string) file_get_contents(base_path("tests/Fixtures/giphy/{$name}.json"));
    }
}
