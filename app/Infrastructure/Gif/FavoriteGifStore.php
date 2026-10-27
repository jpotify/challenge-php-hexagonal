<?php

namespace App\Infrastructure\Gif;

use App\Domain\Gif\Alias;
use App\Domain\Gif\Exception\FavoriteAlreadyExists;
use App\Domain\Gif\FavoriteGif;
use App\Domain\Gif\GifId;
use App\Domain\Shared\ValueObject\UserId;
use App\Infrastructure\Persistence\Eloquent\FavoriteGifModel;
use DateTimeImmutable;
use Illuminate\Database\UniqueConstraintViolationException;

final class FavoriteGifStore
{
    public function save(FavoriteGif $favorite): FavoriteGif
    {
        try {
            $model = FavoriteGifModel::query()->create([
                'user_id' => $favorite->userId->value,
                'gif_id' => $favorite->gifId->value,
                'alias' => $favorite->alias->value,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw FavoriteAlreadyExists::forGif($favorite->gifId);
        }

        return new FavoriteGif(
            id: $model->id,
            userId: new UserId($model->user_id),
            gifId: new GifId($model->gif_id),
            alias: new Alias($model->alias),
            createdAt: $model->created_at === null ? null : DateTimeImmutable::createFromInterface($model->created_at),
        );
    }
}
