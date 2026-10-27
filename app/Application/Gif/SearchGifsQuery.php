<?php

namespace App\Application\Gif;

final readonly class SearchGifsQuery
{
    public function __construct(
        public string $query,
        public ?int $limit = null,
        public ?int $offset = null,
    ) {}
}
