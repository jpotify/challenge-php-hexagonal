<?php

namespace Tests\Unit\Domain\Audit\Service;

use App\Domain\Audit\Service\SensitiveDataRedactor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SensitiveDataRedactorTest extends TestCase
{
    public function test_masks_sensitive_keys_at_any_depth_ignoring_case(): void
    {
        $redacted = (new SensitiveDataRedactor)->redactArray([
            'email' => 'a@b.com',
            'Password' => 'hunter2',
            'nested' => ['access_token' => 'abc', 'keep' => 'me'],
        ]);

        $this->assertSame([
            'email' => 'a@b.com',
            'Password' => '[REDACTED]',
            'nested' => ['access_token' => '[REDACTED]', 'keep' => 'me'],
        ], $redacted);
    }

    public function test_redacts_json_bodies(): void
    {
        $body = (new SensitiveDataRedactor)->redactBody('{"password":"hunter2","a":"ü/b"}');

        $this->assertSame('{"password":"[REDACTED]","a":"ü/b"}', $body);
    }

    /**
     * @return array<string, array{0: string|null}>
     */
    public static function untouchedBodies(): array
    {
        return [
            'null' => [null],
            'empty' => [''],
            'plain text' => ['plain text'],
            'json scalar' => ['"just a string"'],
        ];
    }

    #[DataProvider('untouchedBodies')]
    public function test_returns_bodies_without_structure_unchanged(?string $body): void
    {
        $this->assertSame($body, (new SensitiveDataRedactor)->redactBody($body));
    }
}
