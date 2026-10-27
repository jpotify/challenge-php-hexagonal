<?php

namespace Tests\Unit\Domain\Gif;

use App\Domain\Gif\Exception\InvalidGifId;
use App\Domain\Gif\GifId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GifIdTest extends TestCase
{
    public function test_accepts_alphanumeric_giphy_ids(): void
    {
        $this->assertSame('xT9IgzoKnwFNmISR8I', (new GifId(' xT9IgzoKnwFNmISR8I '))->value);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidIds(): array
    {
        return [
            'empty' => [''],
            'path traversal' => ['../secret'],
            'whitespace inside' => ['abc 123'],
            'symbols' => ['abc-123'],
            'too long' => [str_repeat('a', 65)],
        ];
    }

    #[DataProvider('invalidIds')]
    public function test_rejects_malformed_ids(string $id): void
    {
        $this->expectException(InvalidGifId::class);

        new GifId($id);
    }
}
