<?php

declare(strict_types=1);

namespace Tests\Middleware;

use DemonDextralHorn\Middleware\PrefetchTriggerMiddleware;
use DemonDextralHorn\Jobs\DemonDextralHornJob;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Illuminate\Support\Facades\Queue;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use DemonDextralHorn\Enums\HttpHeaderType;
use DemonDextralHorn\Enums\PrefetchType;
use Tests\TestCase;

#[CoversClass(PrefetchTriggerMiddleware::class)]
final class PrefetchTriggerMiddlewareTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Fake the queue
        Queue::fake();
        config(['demon-dextral-horn.defaults.enabled' => true]);
    }

    #[Test]
    public function it_tests_when_response_is_NOT_successful_by_resolving_middleware(): void
    {
        /* SETUP */
        $prefetchTriggerMiddleware = $this->app->make(PrefetchTriggerMiddleware::class);
        $request = Request::create('/some/uri', Request::METHOD_GET);
        $response = new Response('error', Response::HTTP_INTERNAL_SERVER_ERROR);

        /* EXECUTE */
        $prefetchTriggerMiddleware->terminate($request, $response);

        /* ASSERT */
        Queue::assertNothingPushed(DemonDextralHornJob::class);
    }

    #[Test]
    public function it_tests_when_response_is_NOT_successful(): void
    {
        /* EXECUTE */
        $this->get('/prefetch-route-error');

        /* ASSERT */
        Queue::assertNothingPushed(DemonDextralHornJob::class);
    }

    #[Test]
    public function it_tests_job_is_dispatched_when_prefetch_header_is_NOT_set(): void
    {
        /* SETUP */
        $queueConnection = config('demon-dextral-horn.defaults.queue_connection');
        $queueName = config('demon-dextral-horn.defaults.queue_name');

        /* EXECUTE */
        $this->get('/prefetch-route-success');

        /* ASSERT */
        Queue::assertPushed(DemonDextralHornJob::class, function ($job) use ($queueConnection, $queueName) {
            return $job->queue === $queueName
                && $job->connection === $queueConnection;
        });
    }

    #[Test]
    public function it_tests_job_is_NOT_dispatched_when_prefetch_header_is_auto(): void
    {
        /* SETUP */
        $prefetchHeaderName = config('demon-dextral-horn.defaults.prefetch_header');

        /* EXECUTE */
        $this->withHeaders([$prefetchHeaderName => PrefetchType::AUTO->value])
            ->get('/prefetch-route-success');

        /* ASSERT */
        Queue::assertNothingPushed(DemonDextralHornJob::class);
    }

    #[Test]
    public function it_tests_whether_job_is_dispatched_when_prefetch_header_is_none(): void
    {
        /* SETUP */
        $prefetchHeaderName = config('demon-dextral-horn.defaults.prefetch_header');

        /* EXECUTE */
        $this->withHeaders([$prefetchHeaderName => PrefetchType::NONE->value])
            ->get('/prefetch-route-success');

        /* ASSERT */
        Queue::assertPushed(DemonDextralHornJob::class);
    }

    #[Test]
    public function it_tests_whether_job_is_dispatched_when_header_is_NOT_prefetch_header(): void
    {
        /* SETUP */
        $randomHeader = 'some_other_header';

        /* EXECUTE */
        $this->withHeaders([$randomHeader => 'some_value'])
            ->get('/prefetch-route-success');

        /* ASSERT */
        Queue::assertPushed(DemonDextralHornJob::class);
    }

    #[Test]
    public function it_tests_if_job_is_dispatched_when_other_headers_are_present(): void
    {
        /* SETUP */
        $prefetchHeaderName = config('demon-dextral-horn.defaults.prefetch_header');

        /* EXECUTE */
        $this->withHeaders([
            HttpHeaderType::AUTHORIZATION->value => 'Bearer random_token',
            HttpHeaderType::ACCEPT->value => 'application/json',
            HttpHeaderType::ACCEPT_LANGUAGE->value => 'en-US',
            $prefetchHeaderName => PrefetchType::NONE->value,
        ])
            ->get('/prefetch-route-success');

        /* ASSERT */
        Queue::assertPushed(DemonDextralHornJob::class);
    }

    #[Test]
    public function it_tests_post_route_with_payload_getting_dispatched()
    {
        /* SETUP */
        $prefetchHeaderName = config('demon-dextral-horn.defaults.prefetch_header');
        $payload = ['key' => 'value'];

        /* EXECUTE */
        $this->withHeaders([
            HttpHeaderType::AUTHORIZATION->value => 'Bearer random_token',
            HttpHeaderType::ACCEPT->value => 'application/json',
            HttpHeaderType::ACCEPT_LANGUAGE->value => 'en-US',
            $prefetchHeaderName => PrefetchType::NONE->value,
        ])
            ->post('/post/prefetch-route-success', $payload);

        /* ASSERT */
        Queue::assertPushed(DemonDextralHornJob::class);
    }

    #[Test]
    public function it_tests_login_request_with_laravel_session_cookie(): void
    {
        /* SETUP */
        $loginPayload = ['email' => 'user@example.com', 'password' => 'password'];

        /* EXECUTE */
        $this->withHeaders([
            HttpHeaderType::AUTHORIZATION->value => 'Bearer random_token',
            HttpHeaderType::ACCEPT->value => 'application/json',
            HttpHeaderType::ACCEPT_LANGUAGE->value => 'en-US',
        ])
            ->post('/post/prefetch-login-success', $loginPayload);

        /* ASSERT */
        Queue::assertPushed(DemonDextralHornJob::class);
    }

    #[Test]
    public function it_tests_job_is_NOT_dispatched_when_package_is_disabled(): void
    {
        /* SETUP */
        config(['demon-dextral-horn.defaults.enabled' => false]);

        /* EXECUTE */
        $this->get('/prefetch-route-success');

        /* ASSERT */
        Queue::assertNothingPushed(DemonDextralHornJob::class);
    }
}
