<?php

namespace Tests\Unit\Domain\Gif;

use App\Domain\Gif\Exception\InvalidSearchCriteria;
use App\Domain\Gif\SearchCriteria;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SearchCriteriaTest extends TestCase
{
    public function test_trims_the_query_and_applies_defaults(): void
    {
        $criteria = new SearchCriteria('  funny cats ');

        $this->assertSame('funny cats', $criteria->query);
        $this->assertSame(25, $criteria->limit);
        $this->assertSame(0, $criteria->offset);
    }

    public function test_accepts_the_boundary_values(): void
    {
        $criteria = new SearchCriteria(str_repeat('a', 50), 50, 4999);

        $this->assertSame(50, $criteria->limit);
        $this->assertSame(4999, $criteria->offset);
    }

    /**
     * @return array<string, array{0: string, 1: int|null, 2: int|null}>
     */
    public static function invalidCriteria(): array
    {
        return [
            'blank query' => ['   ', null, null],
            'query too long' => [str_repeat('a', 51), null, null],
            'zero limit' => ['cat', 0, null],
            'limit above maximum' => ['cat', 51, null],
            'negative offset' => ['cat', null, -1],
            'offset above maximum' => ['cat', null, 5000],
        ];
    }

    #[DataProvider('invalidCriteria')]
    public function test_rejects_invalid_criteria(string $query, ?int $limit, ?int $offset): void
    {
        $this->expectException(InvalidSearchCriteria::class);

        new SearchCriteria($query, $limit, $offset);
    }
}
