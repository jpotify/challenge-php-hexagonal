<?php

namespace Tests\Unit\Application\Gif;

use App\Application\Gif\SearchGifs;
use App\Application\Gif\SearchGifsQuery;
use App\Domain\Gif\Exception\InvalidSearchCriteria;
use App\Domain\Gif\Gif;
use App\Domain\Gif\GifId;
use App\Domain\Gif\SearchCriteria;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryGifRepository;

class SearchGifsTest extends TestCase
{
    private InMemoryGifRepository $gifs;

    private SearchGifs $searchGifs;

    protected function setUp(): void
    {
        $this->gifs = new InMemoryGifRepository;

        foreach (['aaa1' => 'Happy cat', 'bbb2' => 'Sleepy cat', 'ccc3' => 'Running dog'] as $id => $title) {
            $this->gifs->add(new Gif(new GifId($id), $title, "https://giphy.com/{$id}", null, null, 'g', null));
        }

        $this->searchGifs = new SearchGifs($this->gifs);
    }

    public function test_returns_the_gifs_matching_the_query(): void
    {
        $result = $this->searchGifs->execute(new SearchGifsQuery('cat'));

        $this->assertSame(2, $result->totalCount);
        $this->assertSame(['aaa1', 'bbb2'], array_map(fn (Gif $gif): string => $gif->id->value, $result->gifs));
    }

    public function test_applies_default_limit_and_offset_when_omitted(): void
    {
        $this->searchGifs->execute(new SearchGifsQuery('cat'));

        $this->assertSame(SearchCriteria::DEFAULT_LIMIT, $this->gifs->lastCriteria?->limit);
        $this->assertSame(0, $this->gifs->lastCriteria->offset);
    }

    public function test_passes_limit_and_offset_to_the_repository(): void
    {
        $result = $this->searchGifs->execute(new SearchGifsQuery('cat', limit: 1, offset: 1));

        $this->assertSame(['bbb2'], array_map(fn (Gif $gif): string => $gif->id->value, $result->gifs));
        $this->assertSame(1, $result->offset);
    }

    public function test_rejects_a_blank_query_without_calling_the_repository(): void
    {
        $this->expectException(InvalidSearchCriteria::class);

        try {
            $this->searchGifs->execute(new SearchGifsQuery('   '));
        } finally {
            $this->assertNull($this->gifs->lastCriteria);
        }
    }
}
