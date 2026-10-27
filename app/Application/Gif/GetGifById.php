<?php

namespace App\Application\Gif;

use App\Domain\Gif\Exception\GifNotFound;
use App\Domain\Gif\Gif;
use App\Domain\Gif\GifId;
use App\Domain\Gif\Repository\GifRepository;

final readonly class GetGifById
{
    public function __construct(private GifRepository $gifs) {}

    /**
     * @throws GifNotFound
     */
    public function execute(string $id): Gif
    {
        $gifId = new GifId($id);

        return $this->gifs->findById($gifId) ?? throw GifNotFound::withId($gifId);
    }
}
