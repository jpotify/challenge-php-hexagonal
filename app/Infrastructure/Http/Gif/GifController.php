<?php

namespace App\Infrastructure\Http\Gif;

use App\Application\Gif\GetGifById;
use App\Application\Gif\SaveFavoriteGif;
use App\Application\Gif\SaveFavoriteGifCommand;
use App\Application\Gif\SearchGifs;
use App\Application\Gif\SearchGifsQuery;
use Illuminate\Http\JsonResponse;

class GifController
{
    public function index(SearchGifsRequest $request, SearchGifs $searchGifs): GifSearchResultResource
    {
        $result = $searchGifs->execute(new SearchGifsQuery(
            query: $request->string('query')->toString(),
            limit: $request->filled('limit') ? $request->integer('limit') : null,
            offset: $request->filled('offset') ? $request->integer('offset') : null,
        ));

        return new GifSearchResultResource($result);
    }

    public function show(string $id, GetGifById $getGifById): GifResource
    {
        return new GifResource($getGifById->execute($id));
    }

    public function storeFavorite(StoreFavoriteGifRequest $request, SaveFavoriteGif $saveFavoriteGif): JsonResponse
    {
        $favorite = $saveFavoriteGif->execute(new SaveFavoriteGifCommand(
            authenticatedUserId: (int) $request->user()?->getAuthIdentifier(),
            userId: $request->integer('user_id'),
            gifId: $request->string('gif_id')->toString(),
            alias: $request->string('alias')->toString(),
        ));

        return (new FavoriteGifResource($favorite))
            ->response()
            ->setStatusCode(JsonResponse::HTTP_CREATED);
    }
}
