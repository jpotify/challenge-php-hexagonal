<?php

namespace App\Domain\Gif\Repository;

use App\Domain\Gif\Exception\FavoriteAlreadyExists;
use App\Domain\Gif\Exception\GifProviderUnavailable;
use App\Domain\Gif\FavoriteGif;
use App\Domain\Gif\Gif;
use App\Domain\Gif\GifId;
use App\Domain\Gif\GifSearchResult;
use App\Domain\Gif\SearchCriteria;

/**
 * Single access point to everything the domain knows about gifs: reading them from
 * wherever they live and persisting the favorites users save.
 */
interface GifRepository
{
    /**
     * @throws GifProviderUnavailable
     */
    public function search(SearchCriteria $criteria): GifSearchResult;

    /**
     * @throws GifProviderUnavailable
     */
    public function findById(GifId $id): ?Gif;

    /**
     * @throws FavoriteAlreadyExists
     */
    public function saveFavorite(FavoriteGif $favorite): FavoriteGif;
}
