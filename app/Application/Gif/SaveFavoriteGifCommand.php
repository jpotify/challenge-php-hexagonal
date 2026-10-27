<?php

namespace App\Application\Gif;

final readonly class SaveFavoriteGifCommand
{
    public function __construct(
        public int $authenticatedUserId,
        public int $userId,
        public string $gifId,
        public string $alias,
    ) {}
}
