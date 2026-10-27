<?php

namespace App\Infrastructure\Http\Exceptions;

use App\Domain\Auth\Exception\InvalidCredentials;
use App\Domain\Gif\Exception\FavoriteAlreadyExists;
use App\Domain\Gif\Exception\FavoriteOwnerMismatch;
use App\Domain\Gif\Exception\GifNotFound;
use App\Domain\Gif\Exception\GifProviderUnavailable;
use App\Domain\Shared\Exception\InvalidInput;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\JsonResponse;
use Throwable;

/**
 * Translates domain failures into HTTP responses so controllers stay free of error handling.
 */
final class DomainExceptionRenderer
{
    public static function register(Exceptions $exceptions): void
    {
        $exceptions->dontReport([
            InvalidCredentials::class,
            FavoriteAlreadyExists::class,
            FavoriteOwnerMismatch::class,
            GifNotFound::class,
            InvalidInput::class,
        ]);

        $exceptions->render(fn (InvalidCredentials $e): JsonResponse => self::json($e, 401));
        $exceptions->render(fn (FavoriteOwnerMismatch $e): JsonResponse => self::json($e, 403));
        $exceptions->render(fn (GifNotFound $e): JsonResponse => self::json($e, 404));
        $exceptions->render(fn (FavoriteAlreadyExists $e): JsonResponse => self::json($e, 409));
        $exceptions->render(fn (InvalidInput $e): JsonResponse => self::json($e, 422));
        $exceptions->render(fn (GifProviderUnavailable $e): JsonResponse => self::json(
            $e,
            $e->timedOut ? 504 : 502,
            $e->timedOut ? 'The gif provider did not respond in time.' : 'The gif provider is currently unavailable.',
        ));
    }

    private static function json(Throwable $exception, int $status, ?string $message = null): JsonResponse
    {
        return new JsonResponse(['message' => $message ?? $exception->getMessage()], $status);
    }
}
