<?php

namespace App\Infrastructure\Gif;

use App\Domain\Gif\Exception\GifProviderUnavailable;
use App\Domain\Gif\Gif;
use App\Domain\Gif\GifId;
use App\Domain\Gif\GifSearchResult;
use App\Domain\Gif\SearchCriteria;
use App\Domain\Shared\Exception\InvalidInput;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class GiphyClient
{
    public function __construct(
        private string $apiKey,
        private string $baseUrl,
        private LoggerInterface $logger,
        private int $timeoutSeconds = 5,
    ) {}

    public function search(SearchCriteria $criteria): GifSearchResult
    {
        $response = $this->get('/gifs/search', [
            'q' => $criteria->query,
            'limit' => $criteria->limit,
            'offset' => $criteria->offset,
        ]);

        if ($response->failed()) {
            throw GifProviderUnavailable::failed();
        }

        /** @var array<int, array<string, mixed>> $items */
        $items = $response->json('data', []);

        return new GifSearchResult(
            gifs: array_values(array_map($this->toGif(...), $items)),
            totalCount: (int) $response->json('pagination.total_count', 0),
            offset: (int) $response->json('pagination.offset', $criteria->offset),
        );
    }

    public function findById(GifId $id): ?Gif
    {
        $response = $this->get('/gifs/'.$id->value);

        if ($response->status() === 404) {
            return null;
        }

        if ($response->failed()) {
            throw GifProviderUnavailable::failed();
        }

        $data = $response->json('data');

        if (! is_array($data) || $data === []) {
            return null;
        }

        return $this->toGif($data);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function get(string $path, array $query = []): Response
    {
        $context = ['path' => $path, 'query' => $query];

        if ($this->apiKey === '') {
            $this->logger->error('GIPHY request skipped: the API key is not configured.', $context);

            throw GifProviderUnavailable::failed();
        }

        $startedAt = hrtime(true);

        try {
            $response = $this->client()->get($path, $query + ['api_key' => $this->apiKey]);
        } catch (ConnectionException $exception) {
            $this->logger->error('GIPHY request timed out or could not connect.', $context + [
                'duration_ms' => $this->elapsedMilliseconds($startedAt),
                'exception' => $exception->getMessage(),
            ]);

            throw GifProviderUnavailable::timedOut($exception);
        } catch (Throwable $exception) {
            $this->logger->error('GIPHY request failed unexpectedly.', $context + [
                'duration_ms' => $this->elapsedMilliseconds($startedAt),
                'exception' => $exception->getMessage(),
            ]);

            throw GifProviderUnavailable::failed($exception);
        }

        $status = $response->status();
        $level = match (true) {
            $status >= 500 => 'error',
            $status >= 400 && $status !== 404 => 'warning',
            default => 'info',
        };

        $this->logger->log($level, 'GIPHY request completed.', $context + [
            'status' => $status,
            'duration_ms' => $this->elapsedMilliseconds($startedAt),
        ]);

        return $response;
    }

    private function elapsedMilliseconds(int $startedAt): float
    {
        return round((hrtime(true) - $startedAt) / 1_000_000, 1);
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->connectTimeout(3)
            ->timeout($this->timeoutSeconds)
            ->retry(
                2,
                200,
                fn (Throwable $exception): bool => $exception instanceof ConnectionException
                    || ($exception instanceof RequestException && $exception->response->serverError()),
                throw: false,
            );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function toGif(array $data): Gif
    {
        try {
            return new Gif(
                id: new GifId((string) ($data['id'] ?? '')),
                title: (string) ($data['title'] ?? ''),
                url: (string) ($data['url'] ?? ''),
                imageUrl: $data['images']['original']['url'] ?? null,
                previewUrl: $data['images']['fixed_height']['url'] ?? null,
                rating: $data['rating'] ?? null,
                username: ($data['username'] ?? '') !== '' ? $data['username'] : null,
            );
        } catch (InvalidInput $exception) {
            throw GifProviderUnavailable::failed($exception);
        }
    }
}
