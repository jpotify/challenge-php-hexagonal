<?php

namespace Tests\Feature\Infrastructure\Http\Gif;

use App\Infrastructure\Persistence\Eloquent\FavoriteGifModel;
use App\Infrastructure\Persistence\Eloquent\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

class GifControllerTest extends TestCase
{
    use InteractsWithApi, RefreshDatabase;

    private UserModel $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpApi();
        $this->user = UserModel::factory()->create();
    }

    public function test_search_returns_401_without_a_token(): void
    {
        $this->getJson('/api/v1/gifs?query=cat')->assertUnauthorized();
    }

    public function test_search_returns_401_with_an_invalid_token(): void
    {
        $this->withToken('not-a-real-token')->getJson('/api/v1/gifs?query=cat')->assertUnauthorized();
    }

    public function test_search_returns_the_collection_and_pagination_from_giphy(): void
    {
        Http::fake(['api.giphy.com/v1/gifs/search*' => Http::response($this->giphyFixture('search'))]);

        $this->getJson('/api/v1/gifs?query=cat&limit=2&offset=10', $this->authHeadersFor($this->user))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', 'xT9IgzoKnwFNmISR8I')
            ->assertJsonPath('data.0.title', 'Funny Cat GIF')
            ->assertJsonPath('data.0.image_url', 'https://media.giphy.com/media/xT9IgzoKnwFNmISR8I/giphy.gif')
            ->assertJsonPath('data.1.username', null)
            ->assertJsonPath('pagination', ['total_count' => 120, 'count' => 2, 'offset' => 10]);
    }

    public function test_search_forwards_the_filters_and_the_api_key_to_giphy(): void
    {
        Http::fake(['api.giphy.com/*' => Http::response($this->giphyFixture('search'))]);

        $this->getJson('/api/v1/gifs?query=funny%20cat&limit=5&offset=15', $this->authHeadersFor($this->user))
            ->assertOk();

        Http::assertSent(fn (Request $request): bool => $request['q'] === 'funny cat'
            && (int) $request['limit'] === 5
            && (int) $request['offset'] === 15
            && $request['api_key'] === 'test-key');
    }

    public function test_search_applies_default_limit_and_offset_when_they_are_omitted(): void
    {
        Http::fake(['api.giphy.com/*' => Http::response($this->giphyFixture('search'))]);

        $this->getJson('/api/v1/gifs?query=cat', $this->authHeadersFor($this->user))->assertOk();

        Http::assertSent(fn (Request $request): bool => (int) $request['limit'] === 25 && (int) $request['offset'] === 0);
    }

    public function test_search_returns_422_when_the_query_is_missing(): void
    {
        $this->getJson('/api/v1/gifs', $this->authHeadersFor($this->user))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['query']);
    }

    public function test_search_returns_422_for_non_numeric_or_out_of_range_limit_and_offset(): void
    {
        $this->getJson('/api/v1/gifs?query=cat&limit=abc&offset=-1', $this->authHeadersFor($this->user))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['limit', 'offset']);

        $this->getJson('/api/v1/gifs?query=cat&limit=51', $this->authHeadersFor($this->user))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['limit']);
    }

    public function test_search_returns_502_when_giphy_fails(): void
    {
        Http::fake(['api.giphy.com/*' => Http::response('boom', 500)]);

        $this->getJson('/api/v1/gifs?query=cat', $this->authHeadersFor($this->user))
            ->assertStatus(502)
            ->assertJsonPath('message', 'The gif provider is currently unavailable.');
    }

    public function test_search_returns_504_when_giphy_times_out(): void
    {
        Http::fake(['api.giphy.com/*' => fn () => throw new ConnectionException('timeout')]);

        $this->getJson('/api/v1/gifs?query=cat', $this->authHeadersFor($this->user))
            ->assertStatus(504);
    }

    public function test_search_returns_502_when_the_giphy_api_key_is_not_configured(): void
    {
        config(['services.giphy.key' => null]);

        $this->getJson('/api/v1/gifs?query=cat', $this->authHeadersFor($this->user))->assertStatus(502);

        Http::assertNothingSent();
    }

    public function test_search_logs_the_giphy_hit_without_leaking_the_api_key(): void
    {
        Http::fake(['api.giphy.com/*' => Http::response($this->giphyFixture('search'))]);
        Log::spy();

        $this->getJson('/api/v1/gifs?query=cat&limit=2', $this->authHeadersFor($this->user))->assertOk();

        Log::shouldHaveReceived('log')->withArgs(function (string $level, string $message, array $context): bool {
            return $level === 'info'
                && $message === 'GIPHY request completed.'
                && $context['path'] === '/gifs/search'
                && $context['query'] === ['q' => 'cat', 'limit' => 2, 'offset' => 0]
                && $context['status'] === 200
                && is_float($context['duration_ms'])
                && ! str_contains((string) json_encode($context), 'test-key');
        })->once();
    }

    public function test_search_logs_an_error_when_giphy_fails(): void
    {
        Http::fake(['api.giphy.com/*' => Http::response('boom', 500)]);
        Log::spy();

        $this->getJson('/api/v1/gifs?query=cat', $this->authHeadersFor($this->user))->assertStatus(502);

        Log::shouldHaveReceived('log')
            ->withArgs(fn (string $level, string $message, array $context): bool => $level === 'error' && $context['status'] === 500)
            ->once();
    }

    public function test_search_logs_an_error_when_giphy_times_out(): void
    {
        Http::fake(['api.giphy.com/*' => fn () => throw new ConnectionException('timeout')]);
        Log::spy();

        $this->getJson('/api/v1/gifs?query=cat', $this->authHeadersFor($this->user))->assertStatus(504);

        Log::shouldHaveReceived('error')
            ->withArgs(fn (string $message, array $context): bool => $message === 'GIPHY request timed out or could not connect.'
                && $context['path'] === '/gifs/search'
                && $context['exception'] === 'timeout')
            ->once();
    }

    public function test_show_logs_a_gif_that_giphy_does_not_know_as_info(): void
    {
        Http::fake(['api.giphy.com/*' => Http::response(['meta' => ['status' => 404]], 404)]);
        Log::spy();

        $this->getJson('/api/v1/gifs/doesnotexist1', $this->authHeadersFor($this->user))->assertNotFound();

        Log::shouldHaveReceived('log')
            ->withArgs(fn (string $level, string $message, array $context): bool => $level === 'info'
                && $context['path'] === '/gifs/doesnotexist1'
                && $context['status'] === 404)
            ->once();
    }

    public function test_search_logs_an_error_when_the_giphy_api_key_is_not_configured(): void
    {
        config(['services.giphy.key' => null]);
        Log::spy();

        $this->getJson('/api/v1/gifs?query=cat', $this->authHeadersFor($this->user))->assertStatus(502);

        Log::shouldHaveReceived('error')
            ->withArgs(fn (string $message): bool => $message === 'GIPHY request skipped: the API key is not configured.')
            ->once();
    }

    public function test_show_returns_401_without_a_token(): void
    {
        $this->getJson('/api/v1/gifs/xT9IgzoKnwFNmISR8I')->assertUnauthorized();
    }

    public function test_show_returns_the_gif_data(): void
    {
        Http::fake(['api.giphy.com/v1/gifs/xT9IgzoKnwFNmISR8I*' => Http::response($this->giphyFixture('gif'))]);

        $this->getJson('/api/v1/gifs/xT9IgzoKnwFNmISR8I', $this->authHeadersFor($this->user))
            ->assertOk()
            ->assertExactJson(['data' => [
                'id' => 'xT9IgzoKnwFNmISR8I',
                'title' => 'Funny Cat GIF',
                'url' => 'https://giphy.com/gifs/cat-xT9IgzoKnwFNmISR8I',
                'image_url' => 'https://media.giphy.com/media/xT9IgzoKnwFNmISR8I/giphy.gif',
                'preview_url' => 'https://media.giphy.com/media/xT9IgzoKnwFNmISR8I/200.gif',
                'rating' => 'g',
                'username' => 'catlover',
            ]]);
    }

    public function test_show_returns_404_when_giphy_does_not_know_the_gif(): void
    {
        Http::fake(['api.giphy.com/*' => Http::response(['meta' => ['status' => 404]], 404)]);

        $this->getJson('/api/v1/gifs/doesnotexist1', $this->authHeadersFor($this->user))
            ->assertNotFound();
    }

    public function test_show_returns_404_without_calling_giphy_for_a_malformed_id(): void
    {
        $this->getJson('/api/v1/gifs/not-valid!', $this->authHeadersFor($this->user))->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_show_returns_502_when_giphy_fails(): void
    {
        Http::fake(['api.giphy.com/*' => Http::response('boom', 500)]);

        $this->getJson('/api/v1/gifs/abc123', $this->authHeadersFor($this->user))->assertStatus(502);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function favoritePayload(array $overrides = []): array
    {
        return array_merge(['gif_id' => 'xT9IgzoKnwFNmISR8I', 'alias' => 'My cat', 'user_id' => $this->user->id], $overrides);
    }

    private function fakeGiphyKnowsTheGif(): void
    {
        Http::fake(['api.giphy.com/v1/gifs/xT9IgzoKnwFNmISR8I*' => Http::response($this->giphyFixture('gif'))]);
    }

    public function test_store_favorite_returns_401_without_a_token(): void
    {
        $this->postJson('/api/v1/favorites', $this->favoritePayload())->assertUnauthorized();

        $this->assertDatabaseCount('favorite_gifs', 0);
    }

    public function test_store_favorite_saves_the_favorite_and_returns_201(): void
    {
        $this->fakeGiphyKnowsTheGif();

        $this->postJson('/api/v1/favorites', $this->favoritePayload(), $this->authHeadersFor($this->user))
            ->assertCreated()
            ->assertJsonPath('data.user_id', $this->user->id)
            ->assertJsonPath('data.gif_id', 'xT9IgzoKnwFNmISR8I')
            ->assertJsonPath('data.alias', 'My cat');

        $this->assertDatabaseHas('favorite_gifs', [
            'user_id' => $this->user->id,
            'gif_id' => 'xT9IgzoKnwFNmISR8I',
            'alias' => 'My cat',
        ]);
    }

    public function test_store_favorite_returns_403_when_the_user_id_is_not_the_authenticated_user(): void
    {
        $otherUser = UserModel::factory()->create();

        $this->postJson('/api/v1/favorites', $this->favoritePayload(['user_id' => $otherUser->id]), $this->authHeadersFor($this->user))
            ->assertForbidden();

        $this->assertDatabaseCount('favorite_gifs', 0);
        Http::assertNothingSent();
    }

    public function test_store_favorite_returns_404_when_the_gif_does_not_exist_in_giphy(): void
    {
        Http::fake(['api.giphy.com/*' => Http::response(['meta' => ['status' => 404]], 404)]);

        $this->postJson('/api/v1/favorites', $this->favoritePayload(), $this->authHeadersFor($this->user))
            ->assertNotFound();

        $this->assertDatabaseCount('favorite_gifs', 0);
    }

    public function test_store_favorite_returns_409_when_the_gif_is_already_a_favorite_of_the_user(): void
    {
        $this->fakeGiphyKnowsTheGif();
        FavoriteGifModel::query()->create(['user_id' => $this->user->id, 'gif_id' => 'xT9IgzoKnwFNmISR8I', 'alias' => 'First']);

        $this->postJson('/api/v1/favorites', $this->favoritePayload(['alias' => 'Second']), $this->authHeadersFor($this->user))
            ->assertConflict();

        $this->assertDatabaseCount('favorite_gifs', 1);
        $this->assertDatabaseHas('favorite_gifs', ['alias' => 'First']);
    }

    public function test_store_favorite_allows_two_users_to_save_the_same_gif(): void
    {
        $this->fakeGiphyKnowsTheGif();
        $otherUser = UserModel::factory()->create();
        FavoriteGifModel::query()->create(['user_id' => $otherUser->id, 'gif_id' => 'xT9IgzoKnwFNmISR8I', 'alias' => 'Theirs']);

        $this->postJson('/api/v1/favorites', $this->favoritePayload(), $this->authHeadersFor($this->user))->assertCreated();

        $this->assertDatabaseCount('favorite_gifs', 2);
    }

    public function test_store_favorite_returns_422_when_the_fields_are_missing(): void
    {
        $this->postJson('/api/v1/favorites', [], $this->authHeadersFor($this->user))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['gif_id', 'alias', 'user_id']);
    }

    public function test_store_favorite_returns_422_for_a_malformed_gif_id_or_user_id(): void
    {
        $this->postJson(
            '/api/v1/favorites',
            $this->favoritePayload(['gif_id' => '../etc', 'user_id' => 'abc']),
            $this->authHeadersFor($this->user),
        )->assertUnprocessable()->assertJsonValidationErrors(['gif_id', 'user_id']);

        Http::assertNothingSent();
    }

    public function test_store_favorite_returns_422_for_an_alias_longer_than_100_characters(): void
    {
        $this->postJson(
            '/api/v1/favorites',
            $this->favoritePayload(['alias' => str_repeat('a', 101)]),
            $this->authHeadersFor($this->user),
        )->assertUnprocessable()->assertJsonValidationErrors(['alias']);
    }

    public function test_store_favorite_returns_502_when_giphy_is_unavailable(): void
    {
        Http::fake(['api.giphy.com/*' => Http::response('boom', 500)]);

        $this->postJson('/api/v1/favorites', $this->favoritePayload(), $this->authHeadersFor($this->user))
            ->assertStatus(502);

        $this->assertDatabaseCount('favorite_gifs', 0);
    }
}
