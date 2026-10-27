<?php

namespace Tests\Unit\Application\Audit;

use App\Application\Audit\RecordRequestLog;
use App\Application\Audit\RecordRequestLogCommand;
use App\Domain\Audit\Service\SensitiveDataRedactor;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryRequestLogRepository;

class RecordRequestLogTest extends TestCase
{
    public function test_persists_the_interaction_with_credentials_redacted(): void
    {
        $logs = new InMemoryRequestLogRepository;

        (new RecordRequestLog($logs, new SensitiveDataRedactor))->execute(new RecordRequestLogCommand(
            userId: 3,
            service: 'auth.login',
            requestBody: '{"email":"a@b.com","password":"hunter2"}',
            responseCode: 200,
            responseBody: '{"token":"abc","token_type":"Bearer"}',
            ipAddress: '10.0.0.1',
        ));

        $this->assertCount(1, $logs->saved);
        $log = $logs->saved[0];
        $this->assertSame(3, $log->userId?->value);
        $this->assertSame('auth.login', $log->service);
        $this->assertSame(200, $log->responseCode);
        $this->assertSame('10.0.0.1', $log->ipAddress);
        $this->assertSame('{"email":"a@b.com","password":"[REDACTED]"}', $log->requestBody);
        $this->assertSame('{"token":"[REDACTED]","token_type":"Bearer"}', $log->responseBody);
    }

    public function test_records_anonymous_requests_without_a_user(): void
    {
        $logs = new InMemoryRequestLogRepository;

        (new RecordRequestLog($logs, new SensitiveDataRedactor))->execute(new RecordRequestLogCommand(
            userId: null,
            service: 'gifs.search',
            requestBody: '[]',
            responseCode: 401,
            responseBody: '{"message":"Unauthenticated."}',
            ipAddress: '10.0.0.1',
        ));

        $this->assertNull($logs->saved[0]->userId);
    }
}
