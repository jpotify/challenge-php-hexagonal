<?php

namespace App\Infrastructure\Http\Gif;

use App\Domain\Gif\Gif;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Gif $resource
 */
class GifResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id->value,
            'title' => $this->resource->title,
            'url' => $this->resource->url,
            'image_url' => $this->resource->imageUrl,
            'preview_url' => $this->resource->previewUrl,
            'rating' => $this->resource->rating,
            'username' => $this->resource->username,
        ];
    }
}
