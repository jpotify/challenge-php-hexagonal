<?php

namespace App\Domain\Gif;

final readonly class GifSearchResult
{
    /**
     * @param  list<Gif>  $gifs
     */
    public function __construct(
        public array $gifs,
        public int $totalCount,
        public int $offset,
    ) {}

    public function count(): int
    {
        return count($this->gifs);
    }
}
