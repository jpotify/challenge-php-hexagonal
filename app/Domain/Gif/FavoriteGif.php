<?php

namespace App\Domain\Gif;

use App\Domain\Shared\ValueObject\UserId;
use DateTimeImmutable;

final readonly class FavoriteGif
{
    public function __construct(
        public ?int $id,
        public UserId $userId,
        public GifId $gifId,
        public Alias $alias,
        public ?DateTimeImmutable $createdAt = null,
    ) {}
}
