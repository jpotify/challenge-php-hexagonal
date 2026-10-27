<?php

namespace App\Application\Gif;

use App\Domain\Gif\Alias;
use App\Domain\Gif\Exception\FavoriteAlreadyExists;
use App\Domain\Gif\Exception\FavoriteOwnerMismatch;
use App\Domain\Gif\Exception\GifNotFound;
use App\Domain\Gif\FavoriteGif;
use App\Domain\Gif\GifId;
use App\Domain\Gif\Repository\GifRepository;
use App\Domain\Shared\ValueObject\UserId;

final readonly class SaveFavoriteGif
{
    public function __construct(private GifRepository $gifs) {}

    /**
     * @throws FavoriteOwnerMismatch
     * @throws GifNotFound
     * @throws FavoriteAlreadyExists
     */
    public function execute(SaveFavoriteGifCommand $command): FavoriteGif
    {
        $authenticatedUserId = new UserId($command->authenticatedUserId);
        $userId = new UserId($command->userId);

        if (! $authenticatedUserId->equals($userId)) {
            throw FavoriteOwnerMismatch::create();
        }

        $gifId = new GifId($command->gifId);
        $alias = new Alias($command->alias);

        if ($this->gifs->findById($gifId) === null) {
            throw GifNotFound::withId($gifId);
        }

        return $this->gifs->saveFavorite(new FavoriteGif(null, $userId, $gifId, $alias));
    }
}
