<?php

namespace App\Infrastructure\Http\Middleware;

use App\Application\Audit\RecordRequestLog;
use App\Application\Audit\RecordRequestLogCommand;
use Closure;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Persists every interaction with the API services once the response has been sent.
 */
class AuditApiRequest
{
    public function __construct(
        private readonly RecordRequestLog $recordRequestLog,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $service = $request->route()?->getName();

        if ($service === null) {
            return;
        }

        try {
            $userId = $request->user()?->getAuthIdentifier();

            $this->recordRequestLog->execute(new RecordRequestLogCommand(
                userId: $userId === null ? null : (int) $userId,
                service: $service,
                requestBody: json_encode($request->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null,
                responseCode: $response->getStatusCode(),
                responseBody: $this->responseBody($response),
                ipAddress: (string) $request->ip(),
            ));
        } catch (Throwable $exception) {
            $this->logger->error('The API request could not be recorded in the audit log.', [
                'service' => $service,
                'response_code' => $response->getStatusCode(),
                'ip' => $request->ip(),
                'exception' => $exception,
            ]);
        }
    }

    private function responseBody(Response $response): ?string
    {
        if ($response instanceof StreamedResponse || $response instanceof BinaryFileResponse) {
            return null;
        }

        return $response->getContent() ?: null;
    }
}
