<?php

namespace App\Infrastructure\Providers;

use App\Domain\Audit\Repository\RequestLogRepository;
use App\Domain\Auth\Repository\UserRepository;
use App\Domain\Auth\Service\AccessTokenIssuer;
use App\Domain\Auth\Service\PasswordVerifier;
use App\Domain\Gif\Repository\GifRepository;
use App\Infrastructure\Auth\LaravelPasswordVerifier;
use App\Infrastructure\Auth\PassportAccessTokenIssuer;
use App\Infrastructure\Gif\FavoriteGifStore;
use App\Infrastructure\Gif\GiphyClient;
use App\Infrastructure\Gif\GiphyEloquentGifRepository;
use App\Infrastructure\Persistence\Eloquent\Repository\EloquentRequestLogRepository;
use App\Infrastructure\Persistence\Eloquent\Repository\EloquentUserRepository;
use App\Infrastructure\Persistence\LoggingRequestLogRepository;
use DateInterval;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Psr\Log\LoggerInterface;

/**
 * Binds every domain port to its infrastructure adapter.
 */
class HexagonalServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        UserRepository::class => EloquentUserRepository::class,
        PasswordVerifier::class => LaravelPasswordVerifier::class,
        AccessTokenIssuer::class => PassportAccessTokenIssuer::class,
    ];

    public function register(): void
    {
        $this->app->bind(RequestLogRepository::class, function (): RequestLogRepository {
            $repository = new EloquentRequestLogRepository;

            return config('logging.audit_to_stdout')
                ? new LoggingRequestLogRepository($repository, $this->app->make('log')->channel('audit'))
                : $repository;
        });

        $this->app->bind(GifRepository::class, fn (): GiphyEloquentGifRepository => new GiphyEloquentGifRepository(
            new GiphyClient(
                apiKey: (string) config('services.giphy.key'),
                baseUrl: (string) config('services.giphy.base_url'),
                logger: $this->app->make(LoggerInterface::class),
                timeoutSeconds: (int) config('services.giphy.timeout'),
            ),
            new FavoriteGifStore,
        ));

        // Tokens are issued by the login service, so Passport's own OAuth routes stay disabled.
        Passport::ignoreRoutes();
    }

    public function boot(): void
    {
        Passport::personalAccessTokensExpireIn(
            new DateInterval('PT'.(int) config('auth.token_expiration_minutes').'M'),
        );
    }
}
