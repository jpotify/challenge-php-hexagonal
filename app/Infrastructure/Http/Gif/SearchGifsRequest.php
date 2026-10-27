<?php

namespace App\Infrastructure\Http\Gif;

use App\Domain\Gif\SearchCriteria;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SearchGifsRequest extends FormRequest
{
    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'max:'.SearchCriteria::MAX_QUERY_LENGTH],
            'limit' => ['nullable', 'integer', 'between:1,'.SearchCriteria::MAX_LIMIT],
            'offset' => ['nullable', 'integer', 'between:0,'.SearchCriteria::MAX_OFFSET],
        ];
    }
}
