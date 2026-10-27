<?php

namespace App\Infrastructure\Gif;

use App\Domain\Gif\FavoriteGif;
use App\Domain\Gif\Gif;
use App\Domain\Gif\GifId;
use App\Domain\Gif\GifSearchResult;
use App\Domain\Gif\Repository\GifRepository;
use App\Domain\Gif\SearchCriteria;

/**
 * Reads gifs from GIPHY and persists favorites in the database, behind one repository.
 */
final readonly class GiphyEloquentGifRepository implements GifRepository
{
    public function __construct(
        private GiphyClient $giphy,
        private FavoriteGifStore $favorites,
    ) {}

    public function search(SearchCriteria $criteria): GifSearchResult
    {
        return $this->giphy->search($criteria);
    }

    public function findById(GifId $id): ?Gif
    {
        return $this->giphy->findById($id);
    }

    public function saveFavorite(FavoriteGif $favorite): FavoriteGif
    {
        return $this->favorites->save($favorite);
    }
}
