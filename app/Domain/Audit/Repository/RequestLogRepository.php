<?php

namespace App\Domain\Audit\Repository;

use App\Domain\Audit\RequestLog;

interface RequestLogRepository
{
    public function save(RequestLog $log): void;
}
