<?php

namespace App\Domain\Gif;

final readonly class Gif
{
    public function __construct(
        public GifId $id,
        public string $title,
        public string $url,
        public ?string $imageUrl,
        public ?string $previewUrl,
        public ?string $rating,
        public ?string $username,
    ) {}
}
