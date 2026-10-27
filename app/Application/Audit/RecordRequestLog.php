<?php

namespace App\Application\Audit;

use App\Domain\Audit\Repository\RequestLogRepository;
use App\Domain\Audit\RequestLog;
use App\Domain\Audit\Service\SensitiveDataRedactor;
use App\Domain\Shared\ValueObject\UserId;

final readonly class RecordRequestLog
{
    public function __construct(
        private RequestLogRepository $logs,
        private SensitiveDataRedactor $redactor,
    ) {}

    public function execute(RecordRequestLogCommand $command): void
    {
        $this->logs->save(new RequestLog(
            userId: $command->userId === null ? null : new UserId($command->userId),
            service: $command->service,
            requestBody: $this->redactor->redactBody($command->requestBody),
            responseCode: $command->responseCode,
            responseBody: $this->redactor->redactBody($command->responseBody),
            ipAddress: $command->ipAddress,
        ));
    }
}
