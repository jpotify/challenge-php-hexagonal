<?php

namespace Tests\Unit\Domain\Gif;

use App\Domain\Gif\Alias;
use App\Domain\Gif\Exception\InvalidAlias;
use PHPUnit\Framework\TestCase;

class AliasTest extends TestCase
{
    public function test_trims_the_alias(): void
    {
        $this->assertSame('My cat', (new Alias('  My cat '))->value);
    }

    public function test_rejects_a_blank_alias(): void
    {
        $this->expectException(InvalidAlias::class);

        new Alias('   ');
    }

    public function test_rejects_an_alias_longer_than_the_maximum(): void
    {
        $this->expectException(InvalidAlias::class);

        new Alias(str_repeat('a', Alias::MAX_LENGTH + 1));
    }
}
