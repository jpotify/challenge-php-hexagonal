<?php

namespace App\Infrastructure\Http\Gif;

use App\Domain\Gif\FavoriteGif;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property FavoriteGif $resource
 */
class FavoriteGifResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'user_id' => $this->resource->userId->value,
            'gif_id' => $this->resource->gifId->value,
            'alias' => $this->resource->alias->value,
            'created_at' => $this->resource->createdAt?->format(DATE_ATOM),
        ];
    }
}
