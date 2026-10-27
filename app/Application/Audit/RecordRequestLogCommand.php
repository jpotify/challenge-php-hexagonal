<?php

namespace App\Application\Audit;

final readonly class RecordRequestLogCommand
{
    public function __construct(
        public ?int $userId,
        public string $service,
        public ?string $requestBody,
        public int $responseCode,
        public ?string $responseBody,
        public string $ipAddress,
    ) {}
}
