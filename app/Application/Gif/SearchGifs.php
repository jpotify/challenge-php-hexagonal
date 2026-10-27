<?php

namespace App\Application\Gif;

use App\Domain\Gif\GifSearchResult;
use App\Domain\Gif\Repository\GifRepository;
use App\Domain\Gif\SearchCriteria;

final readonly class SearchGifs
{
    public function __construct(private GifRepository $gifs) {}

    public function execute(SearchGifsQuery $query): GifSearchResult
    {
        return $this->gifs->search(
            new SearchCriteria($query->query, $query->limit, $query->offset),
        );
    }
}
