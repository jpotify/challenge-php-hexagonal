<?php

namespace App\Infrastructure\Http\Gif;

use App\Domain\Gif\Alias;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreFavoriteGifRequest extends FormRequest
{
    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'gif_id' => ['required', 'string', 'alpha_num:ascii', 'max:64'],
            'alias' => ['required', 'string', 'max:'.Alias::MAX_LENGTH],
            'user_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
