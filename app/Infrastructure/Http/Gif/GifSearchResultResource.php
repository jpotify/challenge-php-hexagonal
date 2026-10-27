<?php

namespace App\Infrastructure\Http\Gif;

use App\Domain\Gif\GifSearchResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property GifSearchResult $resource
 */
class GifSearchResultResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => GifResource::collection($this->resource->gifs)->resolve($request),
            'pagination' => [
                'total_count' => $this->resource->totalCount,
                'count' => $this->resource->count(),
                'offset' => $this->resource->offset,
            ],
        ];
    }
}
