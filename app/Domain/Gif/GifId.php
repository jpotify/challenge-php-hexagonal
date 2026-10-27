<?php

namespace App\Domain\Gif;

use App\Domain\Gif\Exception\InvalidGifId;

final readonly class GifId
{
    public string $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        if (preg_match('/^[A-Za-z0-9]{1,64}$/', $value) !== 1) {
            throw InvalidGifId::forValue($value);
        }

        $this->value = $value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
