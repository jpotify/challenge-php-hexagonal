<?php

namespace App\Infrastructure\Http\Auth;

use App\Domain\Auth\AccessToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property AccessToken $resource
 */
class AccessTokenResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->resource->value,
            'token_type' => 'Bearer',
            'expires_in' => max(0, $this->resource->expiresAt->getTimestamp() - time()),
            'expires_at' => $this->resource->expiresAt->format(DATE_ATOM),
        ];
    }

    /**
     * Credentials must never be cached by clients or intermediaries.
     */
    public function withResponse(Request $request, JsonResponse $response): void
    {
        $response->headers->set('Cache-Control', 'no-store');
    }
}
