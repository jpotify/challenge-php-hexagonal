<?php

namespace Tests\Feature\Infrastructure\Http\Middleware;

use App\Domain\Audit\Repository\RequestLogRepository;
use App\Infrastructure\Persistence\Eloquent\RequestLogModel;
use App\Infrastructure\Persistence\Eloquent\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

class AuditApiRequestTest extends TestCase
{
    use InteractsWithApi, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpApi();
    }

    public function test_logs_a_login_with_credentials_and_token_redacted(): void
    {
        UserModel::factory()->create(['email' => 'demo@example.com', 'password' => 'secret-pass']);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->postJson('/api/v1/auth/login', ['email' => 'demo@example.com', 'password' => 'secret-pass'])
            ->assertOk();

        $log = RequestLogModel::query()->sole();
        $this->assertSame('auth.login', $log->service);
        $this->assertSame(200, $log->response_code);
        $this->assertSame('203.0.113.9', $log->ip_address);
        $this->assertSame('{"email":"demo@example.com","password":"[REDACTED]"}', $log->request_body);
        $this->assertStringContainsString('"token":"[REDACTED]"', (string) $log->response_body);
        $this->assertStringNotContainsString('secret-pass', (string) $log->request_body);
    }

    public function test_logs_a_failed_login_without_a_user(): void
    {
        $this->postJson('/api/v1/auth/login', ['email' => 'nobody@example.com', 'password' => 'x'])
            ->assertUnauthorized();

        $log = RequestLogModel::query()->sole();
        $this->assertNull($log->user_id);
        $this->assertSame(401, $log->response_code);
    }

    public function test_logs_an_authenticated_request_with_user_and_response_body(): void
    {
        Http::fake(['api.giphy.com/*' => Http::response($this->giphyFixture('search'))]);
        $user = UserModel::factory()->create();

        $response = $this->getJson('/api/v1/gifs?query=cat&limit=2', $this->authHeadersFor($user))->assertOk();

        $log = RequestLogModel::query()->sole();
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('gifs.search', $log->service);
        $this->assertSame(200, $log->response_code);
        $this->assertSame(['query' => 'cat', 'limit' => '2'], json_decode((string) $log->request_body, true));
        $this->assertSame($response->json(), json_decode((string) $log->response_body, true));
    }

    public function test_logs_a_rejected_unauthenticated_request(): void
    {
        $this->getJson('/api/v1/gifs?query=cat')->assertUnauthorized();

        $log = RequestLogModel::query()->sole();
        $this->assertNull($log->user_id);
        $this->assertSame('gifs.search', $log->service);
        $this->assertSame(401, $log->response_code);
    }

    public function test_logs_validation_errors_and_domain_failures_of_the_favorites_service(): void
    {
        $user = UserModel::factory()->create();
        $headers = $this->authHeadersFor($user);

        $this->postJson('/api/v1/favorites', [], $headers)->assertUnprocessable();
        $this->postJson('/api/v1/favorites', ['gif_id' => 'abc', 'alias' => 'x', 'user_id' => $user->id + 1], $headers)
            ->assertForbidden();

        $this->assertSame([422, 403], RequestLogModel::query()->orderBy('id')->pluck('response_code')->all());
        $this->assertSame(
            [$user->id, $user->id],
            RequestLogModel::query()->orderBy('id')->pluck('user_id')->all(),
        );
    }

    public function test_does_not_log_requests_to_unknown_routes(): void
    {
        $this->getJson('/api/v1/unknown')->assertNotFound();

        $this->assertDatabaseCount('api_request_logs', 0);
    }

    public function test_logs_an_error_without_breaking_the_response_when_the_audit_log_cannot_be_saved(): void
    {
        $this->mock(RequestLogRepository::class)
            ->shouldReceive('save')
            ->andThrow(new RuntimeException('database is down'));
        Log::spy();

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->postJson('/api/v1/auth/login', ['email' => 'nobody@example.com', 'password' => 'x'])
            ->assertUnauthorized();

        Log::shouldHaveReceived('error')
            ->withArgs(fn (string $message, array $context): bool => $message === 'The API request could not be recorded in the audit log.'
                && $context['service'] === 'auth.login'
                && $context['response_code'] === 401
                && $context['ip'] === '203.0.113.9'
                && $context['exception'] instanceof RuntimeException)
            ->once();
    }

    /**
     * Points the "audit" channel at a temp file instead of stdout so its output can be read back.
     */
    private function redirectAuditChannelToFile(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'audit');
        $this->beforeApplicationDestroyed(fn () => @unlink($path));

        config(['logging.channels.audit.handler_with.stream' => $path]);

        return $path;
    }

    public function test_writes_each_audited_interaction_as_a_json_line_to_the_audit_channel_when_enabled(): void
    {
        $path = $this->redirectAuditChannelToFile();
        config(['logging.audit_to_stdout' => true]);
        UserModel::factory()->create(['email' => 'demo@example.com', 'password' => 'secret-pass']);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->postJson('/api/v1/auth/login', ['email' => 'demo@example.com', 'password' => 'secret-pass'])
            ->assertOk();

        $lines = array_filter(explode("\n", (string) file_get_contents($path)));
        $this->assertCount(1, $lines);
        $entry = json_decode((string) reset($lines), true);
        $this->assertSame('api.audit', $entry['message']);
        $this->assertSame('auth.login', $entry['context']['service']);
        $this->assertSame(200, $entry['context']['response_code']);
        $this->assertSame('203.0.113.9', $entry['context']['ip']);
        $this->assertSame('{"email":"demo@example.com","password":"[REDACTED]"}', $entry['context']['request_body']);
        $this->assertStringNotContainsString('secret-pass', (string) file_get_contents($path));
        $this->assertDatabaseCount('api_request_logs', 1);
    }

    public function test_does_not_write_to_the_audit_channel_when_disabled(): void
    {
        $path = $this->redirectAuditChannelToFile();
        config(['logging.audit_to_stdout' => false]);

        $this->postJson('/api/v1/auth/login', ['email' => 'nobody@example.com', 'password' => 'x'])
            ->assertUnauthorized();

        $this->assertSame('', (string) file_get_contents($path));
        $this->assertDatabaseCount('api_request_logs', 1);
    }
}
