<?php

namespace Tests\Unit\Application\Gif;

use App\Application\Gif\GetGifById;
use App\Domain\Gif\Exception\GifNotFound;
use App\Domain\Gif\Exception\GifProviderUnavailable;
use App\Domain\Gif\Exception\InvalidGifId;
use App\Domain\Gif\Gif;
use App\Domain\Gif\GifId;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryGifRepository;

class GetGifByIdTest extends TestCase
{
    public function test_returns_the_gif_found_by_the_repository(): void
    {
        $gif = new Gif(new GifId('abc123'), 'Cat', 'https://giphy.com/abc123', null, null, 'g', null);
        $useCase = new GetGifById((new InMemoryGifRepository)->add($gif));

        $this->assertSame($gif, $useCase->execute('abc123'));
    }

    public function test_throws_not_found_when_the_repository_has_no_such_gif(): void
    {
        $this->expectException(GifNotFound::class);

        (new GetGifById(new InMemoryGifRepository))->execute('missing1');
    }

    public function test_rejects_a_malformed_id_before_calling_the_repository(): void
    {
        $gifs = new InMemoryGifRepository;
        $gifs->unavailable = true;

        $this->expectException(InvalidGifId::class);

        (new GetGifById($gifs))->execute('../etc');
    }

    public function test_propagates_repository_failures(): void
    {
        $gifs = new InMemoryGifRepository;
        $gifs->unavailable = true;

        $this->expectException(GifProviderUnavailable::class);

        (new GetGifById($gifs))->execute('abc123');
    }
}
