<?php

namespace App\Domain\Gif\Exception;

use RuntimeException;
use Throwable;

final class GifProviderUnavailable extends RuntimeException
{
    private function __construct(string $message, public readonly bool $timedOut, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public static function failed(?Throwable $previous = null): self
    {
        return new self('The gif provider returned an unexpected response.', false, $previous);
    }

    public static function timedOut(?Throwable $previous = null): self
    {
        return new self('The gif provider did not respond in time.', true, $previous);
    }
}
