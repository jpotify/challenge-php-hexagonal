<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $gif_id
 * @property string $alias
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('favorite_gifs')]
#[Fillable(['user_id', 'gif_id', 'alias'])]
class FavoriteGifModel extends Model {}
