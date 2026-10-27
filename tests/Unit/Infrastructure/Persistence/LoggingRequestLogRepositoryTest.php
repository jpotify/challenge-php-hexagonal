<?php

namespace Tests\Unit\Infrastructure\Persistence;

use App\Domain\Audit\Repository\RequestLogRepository;
use App\Domain\Audit\RequestLog;
use App\Domain\Shared\ValueObject\UserId;
use App\Infrastructure\Persistence\LoggingRequestLogRepository;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Fakes\InMemoryRequestLogRepository;

class LoggingRequestLogRepositoryTest extends TestCase
{
    private function auditLog(): RequestLog
    {
        return new RequestLog(new UserId(3), 'gifs.search', '{"query":"cat"}', 200, '{"data":[]}', '10.0.0.1');
    }

    public function test_logs_the_audit_record_and_delegates_the_persistence(): void
    {
        $handler = new TestHandler;
        $inner = new InMemoryRequestLogRepository;
        $log = $this->auditLog();

        (new LoggingRequestLogRepository($inner, new Logger('audit', [$handler])))->save($log);

        $this->assertSame([$log], $inner->saved);
        $this->assertCount(1, $handler->getRecords());
        $record = $handler->getRecords()[0];
        $this->assertSame('api.audit', $record->message);
        $this->assertSame([
            'user_id' => 3,
            'service' => 'gifs.search',
            'request_body' => '{"query":"cat"}',
            'response_code' => 200,
            'response_body' => '{"data":[]}',
            'ip' => '10.0.0.1',
        ], $record->context);
    }

    public function test_still_logs_the_record_when_the_persistence_fails(): void
    {
        $handler = new TestHandler;
        $failing = new class implements RequestLogRepository
        {
            public function save(RequestLog $log): void
            {
                throw new RuntimeException('database is down');
            }
        };

        try {
            (new LoggingRequestLogRepository($failing, new Logger('audit', [$handler])))->save($this->auditLog());
            $this->fail('The persistence failure must not be swallowed.');
        } catch (RuntimeException) {
            $this->assertTrue($handler->hasInfoThatContains('api.audit'));
        }
    }

    public function test_records_anonymous_requests_with_a_null_user(): void
    {
        $handler = new TestHandler;
        $log = new RequestLog(null, 'auth.login', '{}', 401, '{}', '10.0.0.1');

        (new LoggingRequestLogRepository(new InMemoryRequestLogRepository, new Logger('audit', [$handler])))->save($log);

        $this->assertNull($handler->getRecords()[0]->context['user_id']);
    }
}
