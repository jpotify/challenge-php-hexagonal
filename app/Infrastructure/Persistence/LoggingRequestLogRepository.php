<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Audit\Repository\RequestLogRepository;
use App\Domain\Audit\RequestLog;
use Psr\Log\LoggerInterface;

/**
 * Writes every audit record to a logger (stdout) before delegating the actual persistence.
 */
final readonly class LoggingRequestLogRepository implements RequestLogRepository
{
    public function __construct(
        private RequestLogRepository $inner,
        private LoggerInterface $logger,
    ) {}

    public function save(RequestLog $log): void
    {
        $this->logger->info('api.audit', [
            'user_id' => $log->userId?->value,
            'service' => $log->service,
            'request_body' => $log->requestBody,
            'response_code' => $log->responseCode,
            'response_body' => $log->responseBody,
            'ip' => $log->ipAddress,
        ]);

        $this->inner->save($log);
    }
}
