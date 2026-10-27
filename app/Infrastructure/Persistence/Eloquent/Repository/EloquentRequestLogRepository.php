<?php

namespace App\Infrastructure\Persistence\Eloquent\Repository;

use App\Domain\Audit\Repository\RequestLogRepository;
use App\Domain\Audit\RequestLog;
use App\Infrastructure\Persistence\Eloquent\RequestLogModel;

final class EloquentRequestLogRepository implements RequestLogRepository
{
    public function save(RequestLog $log): void
    {
        RequestLogModel::query()->create([
            'user_id' => $log->userId?->value,
            'service' => $log->service,
            'request_body' => $log->requestBody,
            'response_code' => $log->responseCode,
            'response_body' => $log->responseBody,
            'ip_address' => $log->ipAddress,
            'created_at' => $log->createdAt ?? now(),
        ]);
    }
}
