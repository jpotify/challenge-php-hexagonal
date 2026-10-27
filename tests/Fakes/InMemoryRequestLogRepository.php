<?php

namespace Tests\Fakes;

use App\Domain\Audit\Repository\RequestLogRepository;
use App\Domain\Audit\RequestLog;

final class InMemoryRequestLogRepository implements RequestLogRepository
{
    /** @var list<RequestLog> */
    public array $saved = [];

    public function save(RequestLog $log): void
    {
        $this->saved[] = $log;
    }
}
