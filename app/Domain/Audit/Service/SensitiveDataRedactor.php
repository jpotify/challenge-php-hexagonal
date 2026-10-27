<?php

namespace App\Domain\Audit\Service;

/**
 * Masks credentials and secrets before a payload is persisted in the audit log.
 */
final class SensitiveDataRedactor
{
    public const string MASK = '[REDACTED]';

    private const array SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'token',
        'access_token',
        'refresh_token',
        'client_secret',
        'secret',
        'authorization',
    ];

    /**
     * @param  array<array-key, mixed>  $payload
     * @return array<array-key, mixed>
     */
    public function redactArray(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::SENSITIVE_KEYS, true)) {
                $payload[$key] = self::MASK;

                continue;
            }

            if (is_array($value)) {
                $payload[$key] = $this->redactArray($value);
            }
        }

        return $payload;
    }

    /**
     * Redacts a raw body. Non-JSON bodies are returned as-is when they hold no structure to inspect.
     */
    public function redactBody(?string $body): ?string
    {
        if ($body === null || $body === '') {
            return $body;
        }

        $decoded = json_decode($body, true);

        if (! is_array($decoded)) {
            return $body;
        }

        return json_encode($this->redactArray($decoded), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null;
    }
}
