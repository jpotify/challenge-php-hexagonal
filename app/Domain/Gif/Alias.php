<?php

namespace App\Domain\Gif;

use App\Domain\Gif\Exception\InvalidAlias;

final readonly class Alias
{
    public const int MAX_LENGTH = 100;

    public string $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        if ($value === '' || mb_strlen($value) > self::MAX_LENGTH) {
            throw InvalidAlias::withMaxLength(self::MAX_LENGTH);
        }

        $this->value = $value;
    }
}
