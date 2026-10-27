<?php

namespace App\Domain\Gif;

use App\Domain\Gif\Exception\InvalidSearchCriteria;

final readonly class SearchCriteria
{
    public const int DEFAULT_LIMIT = 25;

    public const int MAX_LIMIT = 50;

    public const int MAX_OFFSET = 4999;

    public const int MAX_QUERY_LENGTH = 50;

    public string $query;

    public int $limit;

    public int $offset;

    public function __construct(string $query, ?int $limit = null, ?int $offset = null)
    {
        $query = trim($query);

        if ($query === '' || mb_strlen($query) > self::MAX_QUERY_LENGTH) {
            throw InvalidSearchCriteria::because('The query must contain between 1 and '.self::MAX_QUERY_LENGTH.' characters.');
        }

        $limit ??= self::DEFAULT_LIMIT;
        $offset ??= 0;

        if ($limit < 1 || $limit > self::MAX_LIMIT) {
            throw InvalidSearchCriteria::because('The limit must be between 1 and '.self::MAX_LIMIT.'.');
        }

        if ($offset < 0 || $offset > self::MAX_OFFSET) {
            throw InvalidSearchCriteria::because('The offset must be between 0 and '.self::MAX_OFFSET.'.');
        }

        $this->query = $query;
        $this->limit = $limit;
        $this->offset = $offset;
    }
}
