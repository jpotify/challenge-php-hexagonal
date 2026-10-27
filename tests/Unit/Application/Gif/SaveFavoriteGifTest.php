<?php

namespace Tests\Unit\Application\Gif;

use App\Application\Gif\SaveFavoriteGif;
use App\Application\Gif\SaveFavoriteGifCommand;
use App\Domain\Gif\Exception\FavoriteAlreadyExists;
use App\Domain\Gif\Exception\FavoriteOwnerMismatch;
use App\Domain\Gif\Exception\GifNotFound;
use App\Domain\Gif\Exception\InvalidAlias;
use App\Domain\Gif\Exception\InvalidGifId;
use App\Domain\Gif\Gif;
use App\Domain\Gif\GifId;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryGifRepository;

class SaveFavoriteGifTest extends TestCase
{
    private InMemoryGifRepository $gifs;

    private SaveFavoriteGif $saveFavoriteGif;

    protected function setUp(): void
    {
        $this->gifs = (new InMemoryGifRepository)->add(
            new Gif(new GifId('abc123'), 'Cat', 'https://giphy.com/abc123', null, null, 'g', null),
        );

        $this->saveFavoriteGif = new SaveFavoriteGif($this->gifs);
    }

    public function test_saves_the_favorite_for_the_authenticated_user(): void
    {
        $favorite = $this->saveFavoriteGif->execute(new SaveFavoriteGifCommand(5, 5, 'abc123', '  My cat  '));

        $this->assertSame(5, $favorite->userId->value);
        $this->assertSame('abc123', $favorite->gifId->value);
        $this->assertSame('My cat', $favorite->alias->value);
        $this->assertCount(1, $this->gifs->favorites);
    }

    public function test_forbids_saving_a_favorite_for_another_user(): void
    {
        $this->expectException(FavoriteOwnerMismatch::class);

        try {
            $this->saveFavoriteGif->execute(new SaveFavoriteGifCommand(5, 6, 'abc123', 'My cat'));
        } finally {
            $this->assertSame([], $this->gifs->favorites);
        }
    }

    public function test_rejects_a_gif_that_does_not_exist_in_the_repository(): void
    {
        $this->expectException(GifNotFound::class);

        $this->saveFavoriteGif->execute(new SaveFavoriteGifCommand(5, 5, 'missing1', 'My cat'));
    }

    public function test_rejects_a_gif_already_saved_by_the_user(): void
    {
        $this->saveFavoriteGif->execute(new SaveFavoriteGifCommand(5, 5, 'abc123', 'First'));

        $this->expectException(FavoriteAlreadyExists::class);

        $this->saveFavoriteGif->execute(new SaveFavoriteGifCommand(5, 5, 'abc123', 'Second'));
    }

    public function test_allows_different_users_to_save_the_same_gif(): void
    {
        $this->saveFavoriteGif->execute(new SaveFavoriteGifCommand(5, 5, 'abc123', 'Mine'));
        $this->saveFavoriteGif->execute(new SaveFavoriteGifCommand(6, 6, 'abc123', 'Mine too'));

        $this->assertCount(2, $this->gifs->favorites);
    }

    public function test_rejects_an_invalid_gif_id(): void
    {
        $this->expectException(InvalidGifId::class);

        $this->saveFavoriteGif->execute(new SaveFavoriteGifCommand(5, 5, 'not valid!', 'My cat'));
    }

    public function test_rejects_a_blank_alias(): void
    {
        $this->expectException(InvalidAlias::class);

        $this->saveFavoriteGif->execute(new SaveFavoriteGifCommand(5, 5, 'abc123', '   '));
    }
}
