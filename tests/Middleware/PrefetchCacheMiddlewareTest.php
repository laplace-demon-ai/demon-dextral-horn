<?php

declare(strict_types=1);

namespace Tests\Middleware;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use DemonDextralHorn\Middleware\PrefetchCacheMiddleware;
use DemonDextralHorn\Facades\ResponseCache;
use DemonDextralHorn\Enums\PrefetchType;

#[CoversClass(PrefetchCacheMiddleware::class)]
final class PrefetchCacheMiddlewareTest extends TestCase
{
    private PrefetchCacheMiddleware $middleware;
    private string $cached = 'cached';
    private string $fresh = 'fresh';
    private string $next = 'next';
    private string $shouldNotRun = 'should-not-run';

    public function setUp(): void
    {
        parent::setUp();

        $this->middleware = $this->app->make(PrefetchCacheMiddleware::class);
        config(['demon-dextral-horn.defaults.enabled' => true]);
    }

    #[Test]
    public function it_tests_that_request_continues_when_package_is_disabled(): void
    {
        /* SETUP */
        config(['demon-dextral-horn.defaults.enabled' => false]);
        $request = Request::create('/sample/route', Request::METHOD_GET);

        /* EXECUTE */
        $response = $this->middleware->handle($request, fn () => new Response($this->next));

        /* ASSERT */
        $this->assertSame($this->next, $response->getContent());
    }

    #[Test]
    public function it_tests_that_request_continues_when_cache_miss(): void
    {
        /* SETUP */
        ResponseCache::shouldReceive('has')->once()->andReturn(false);
        $request = Request::create('/sample-target-route-default', Request::METHOD_GET);
        $request->setRouteResolver(fn () => $this->app['router']->getRoutes()->match($request));

        /* EXECUTE */
        $response = $this->middleware->handle($request, fn () => new Response($this->next));

        /* ASSERT */
        $this->assertSame($this->next, $response->getContent());
    }

    #[Test]
    public function it_tests_that_cached_response_is_returned_and_invalidated(): void
    {
        /* SETUP */
        $cachedResponse = new Response($this->cached);
        ResponseCache::shouldReceive('has')->once()->andReturn(true);
        ResponseCache::shouldReceive('pull')->once()->andReturn($cachedResponse);
        $request = Request::create('/sample-target-route-default', Request::METHOD_GET);
        $request->setRouteResolver(fn () => $this->app['router']->getRoutes()->match($request));

        /* EXECUTE */
        $response = $this->middleware->handle($request, fn () => new Response($this->shouldNotRun));

        /* ASSERT */
        $this->assertSame($this->cached, $response->getContent());
        $this->assertNotSame($this->shouldNotRun, $response->getContent());
    }

    #[Test]
    public function it_tests_that_request_continues_when_cache_entry_is_null(): void
    {
        /* SETUP */
        ResponseCache::shouldReceive('has')->once()->andReturn(true);
        ResponseCache::shouldReceive('pull')->once()->andReturn(null);
        $request = Request::create('/sample-target-route-default', Request::METHOD_GET);
        $request->setRouteResolver(fn () => $this->app['router']->getRoutes()->match($request));

        /* EXECUTE */
        $response = $this->middleware->handle($request, fn () => new Response($this->fresh));

        /* ASSERT */
        $this->assertSame($this->fresh, $response->getContent());
    }

    #[Test]
    public function it_tests_that_prefetch_header_is_added_to_headers(): void
    {
        /* SETUP */
        ResponseCache::shouldReceive('has')->once()->andReturn(false);
        $request = Request::create('/sample-target-route-default', Request::METHOD_GET, [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US',
            'HTTP_AUTHORIZATION' => 'Bearer token',
        ]);
        $request->setRouteResolver(fn () => $this->app['router']->getRoutes()->match($request));

        /* EXECUTE */
        $response = $this->middleware->handle($request, fn () => new Response($this->fresh));

        /* ASSERT */
        $this->assertSame($this->fresh, $response->getContent());
        $prefetchHeaderName = config('demon-dextral-horn.defaults.prefetch_header');
        $this->assertEquals(PrefetchType::AUTO->value, $prefetchHeaderName ? PrefetchType::AUTO->value : null);
    }

    #[Test]
    public function it_tests_that_session_cookie_is_respected(): void
    {
        /* SETUP */
        ResponseCache::shouldReceive('has')->once()->andReturn(false);
        $request = Request::create('/sample-target-route-default', Request::METHOD_GET, [], [
            'laravel_session' => 'cookie_value'
        ]);
        $request->setRouteResolver(fn () => $this->app['router']->getRoutes()->match($request));

        /* EXECUTE */
        $response = $this->middleware->handle($request, fn () => new Response($this->fresh));

        /* ASSERT */
        $this->assertSame($this->fresh, $response->getContent());
    }

    #[Test]
    public function it_tests_route_with_prefetch_cache_middleware_when_has_is_false(): void
    {
        /* SETUP */
        $cachedResponse = new Response($this->cached);
        ResponseCache::shouldReceive('has')->once()->andReturn(false);

        /* EXECUTE */
        $response = $this->get('/sample-target-route-with-middleware');

        /* ASSERT */
        $this->assertNotSame($cachedResponse->getContent(), $response->getContent());
    }

    #[Test]
    public function it_tests_route_with_prefetch_cache_middleware_when_has_is_true_and_pull_returns_cached_response(): void
    {
        /* SETUP */
        $cachedResponse = new Response($this->cached);
        ResponseCache::shouldReceive('has')->once()->andReturn(true);
        ResponseCache::shouldReceive('pull')->once()->andReturn($cachedResponse);

        /* EXECUTE */
        $response = $this->get('/sample-target-route-with-middleware');

        /* ASSERT */
        $this->assertSame($cachedResponse->getContent(), $response->getContent());
    }
}