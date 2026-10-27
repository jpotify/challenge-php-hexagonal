<?php

namespace App\Domain\Audit;

use App\Domain\Shared\ValueObject\UserId;
use DateTimeImmutable;

final readonly class RequestLog
{
    public function __construct(
        public ?UserId $userId,
        public string $service,
        public ?string $requestBody,
        public int $responseCode,
        public ?string $responseBody,
        public string $ipAddress,
        public ?DateTimeImmutable $createdAt = null,
    ) {}
}
