<?php

namespace Tests\Fakes;

use App\Domain\Gif\Exception\FavoriteAlreadyExists;
use App\Domain\Gif\Exception\GifProviderUnavailable;
use App\Domain\Gif\FavoriteGif;
use App\Domain\Gif\Gif;
use App\Domain\Gif\GifId;
use App\Domain\Gif\GifSearchResult;
use App\Domain\Gif\Repository\GifRepository;
use App\Domain\Gif\SearchCriteria;

final class InMemoryGifRepository implements GifRepository
{
    public bool $unavailable = false;

    public ?SearchCriteria $lastCriteria = null;

    /** @var list<FavoriteGif> */
    public array $favorites = [];

    /** @var array<string, Gif> */
    private array $gifs = [];

    public function add(Gif $gif): self
    {
        $this->gifs[$gif->id->value] = $gif;

        return $this;
    }

    public function search(SearchCriteria $criteria): GifSearchResult
    {
        $this->failIfUnavailable();
        $this->lastCriteria = $criteria;

        $matches = array_values(array_filter(
            $this->gifs,
            fn (Gif $gif): bool => str_contains(strtolower($gif->title), strtolower($criteria->query)),
        ));

        return new GifSearchResult(
            array_slice($matches, $criteria->offset, $criteria->limit),
            count($matches),
            $criteria->offset,
        );
    }

    public function findById(GifId $id): ?Gif
    {
        $this->failIfUnavailable();

        return $this->gifs[$id->value] ?? null;
    }

    public function saveFavorite(FavoriteGif $favorite): FavoriteGif
    {
        foreach ($this->favorites as $existing) {
            if ($existing->userId->equals($favorite->userId) && $existing->gifId->equals($favorite->gifId)) {
                throw FavoriteAlreadyExists::forGif($favorite->gifId);
            }
        }

        return $this->favorites[] = new FavoriteGif(
            count($this->favorites) + 1,
            $favorite->userId,
            $favorite->gifId,
            $favorite->alias,
        );
    }

    private function failIfUnavailable(): void
    {
        if ($this->unavailable) {
            throw GifProviderUnavailable::failed();
        }
    }
}
