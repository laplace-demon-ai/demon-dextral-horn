<?php

declare(strict_types=1);

namespace Tests\Cache;

use BadMethodCallException;
use Illuminate\Support\Facades\Event;
use DemonDextralHorn\Cache\CacheKeyGenerator;
use Tests\TestCase;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClass;
use Spatie\ResponseCache\ResponseCacheRepository;
use DemonDextralHorn\Cache\Identifiers\JwtUserIdentifier;
use DemonDextralHorn\Cache\Identifiers\SessionUserIdentifier;
use DemonDextralHorn\Cache\Identifiers\GuestUserIdentifier;
use DemonDextralHorn\Cache\ResponseCache;
use DemonDextralHorn\Data\TargetRouteData;
use DemonDextralHorn\Enums\AuthDriverType;
use DemonDextralHorn\Events\TaggedCacheClearFailedEvent;
use DemonDextralHorn\Cache\Identifiers\Contracts\UserIdentifierInterface;

#[CoversClass(ResponseCache::class)]
final class ResponseCacheTest extends TestCase
{
    private CacheKeyGenerator $cacheKeyGenerator;
    private TargetRouteData $targetRouteData;
    private string $key;

    public function setUp(): void
    {
        parent::setUp();

        $this->cacheKeyGenerator = app(CacheKeyGenerator::class);
        $this->targetRouteData = new TargetRouteData(
            routeName: 'sample.route',
            method: Request::METHOD_GET,
            routeParams: ['id' => 2],
            queryParams: ['page' => 1],
            headers: [],
            cookies: []
        );
        $userIdentifier = app(UserIdentifierInterface::class)->getIdentifierFor($this->targetRouteData);
        $this->key = $this->cacheKeyGenerator->generate($this->targetRouteData, $userIdentifier);
    }

    #[Test]
    public function it_successfully_creates_jwt_user_identifier_based_on_config(): void
    {
        /* SETUP */
        config(['demon-dextral-horn.defaults.auth_driver' => AuthDriverType::JWT->value]);
        $responseCache = app(ResponseCache::class);
        $reflection = new ReflectionClass($responseCache);
        $property = $reflection->getProperty('identifier');

        /* EXECUTE */
        $identifier = $property->getValue($responseCache);

        /* ASSERT */
        $this->assertInstanceOf(JwtUserIdentifier::class, $identifier);
    }

    #[Test]
    public function it_successfully_creates_session_user_identifier_based_on_config(): void
    {
        /* SETUP */
        config(['demon-dextral-horn.defaults.auth_driver' => AuthDriverType::SESSION->value]);
        $responseCache = app(ResponseCache::class);
        $reflection = new ReflectionClass($responseCache);
        $property = $reflection->getProperty('identifier');

        /* EXECUTE */
        $identifier = $property->getValue($responseCache);
        
        /* ASSERT */
        $this->assertInstanceOf(SessionUserIdentifier::class, $identifier);
    }

    #[Test]
    public function it_successfully_creates_guest_user_identifier_based_on_config_as_fallback(): void
    {
        /* SETUP */
        config(['demon-dextral-horn.defaults.auth_driver' => null]);
        $responseCache = app(ResponseCache::class);
        $reflection = new ReflectionClass($responseCache);
        $property = $reflection->getProperty('identifier');

        /* EXECUTE */
        $identifier = $property->getValue($responseCache);
        
        /* ASSERT */
        $this->assertInstanceOf(GuestUserIdentifier::class, $identifier);
    }

    #[Test]
    public function It_stores_to_cache_without_tags(): void
    {
        /* SETUP */
        $response = new Response('ok', Response::HTTP_OK);
        $target = new TargetRouteData('route.name', Request::METHOD_GET, [], [], [], []);
        $sampleTags = ['demon_dextral_horn', 'route_name', 'guest'];
        $sampleKey = config('demon-dextral-horn.defaults.prefetch_prefix') . ':' . '6adf3b47da7fe788a8f0d3446faf7121';
        $this->mock(ResponseCacheRepository::class, function (MockInterface $mock) use ($response, $sampleTags, $sampleKey) {
            // Mock the tags method call
            $mock->shouldReceive('tags')
                ->with(
                    $sampleTags
                )
                ->once()
                ->andReturnSelf();

            // Mock the put method call
            $mock->shouldReceive('put')
                ->with(
                    $sampleKey,
                    $response,
                    config('demon-dextral-horn.defaults.cache_ttl')
                )
                ->once();
        });
        $responseCache = app(ResponseCache::class);

        /* EXECUTE */
        $responseCache->put($response, $target);
    }

    #[Test]
    public function it_tests_the_cache_entry_existence_with_has_method(): void
    {
        /* SETUP */
        $this->mock(ResponseCacheRepository::class, function (MockInterface $mock) {
            $mock->shouldReceive('has')
                ->with($this->key)
                ->once()
                ->andReturn(true);
        });
        $responseCache = app(ResponseCache::class);

        /* EXECUTE */
        $result = $responseCache->has($this->targetRouteData);

        /* ASSERT */
        $this->assertTrue($result);
    }

    #[Test]
    public function it_retrieves_cache_entry_with_get_method(): void
    {
        /* SETUP */
        $response = new Response('cached-response', Response::HTTP_OK);
        $this->mock(ResponseCacheRepository::class, function (MockInterface $mock) use ($response) {
            $mock->shouldReceive('get')
                ->with($this->key)
                ->once()
                ->andReturn($response);
        });
        $responseCache = app(ResponseCache::class);

        /* EXECUTE */
        $result = $responseCache->get($this->targetRouteData);

        /* ASSERT */
        $this->assertSame($response, $result);
    }

    #[Test]
    public function it_forgets_cache_entry_with_forget_method(): void
    {
        /* SETUP */
        $this->mock(ResponseCacheRepository::class, function (MockInterface $mock) {
            $mock->shouldReceive('forget')
                ->with($this->key)
                ->once()
                ->andReturn(true);
        });
        $responseCache = app(ResponseCache::class);
    
        /* EXECUTE */
        $result = $responseCache->forget($this->targetRouteData);
    
        /* ASSERT */
        $this->assertTrue($result);
    }

    #[Test]
    public function it_pulls_cache_entry_with_pull_method(): void
    {
        /* SETUP */
        $response = new Response('pulled-response', Response::HTTP_OK);
        $this->mock(ResponseCacheRepository::class, function (MockInterface $mock) use ($response) {
            $mock->shouldReceive('has')->with($this->key)->once()->andReturn(true);
            $mock->shouldReceive('get')->with($this->key)->once()->andReturn($response);
            $mock->shouldReceive('forget')->with($this->key)->once()->andReturn(true);
        });
        $responseCache = app(ResponseCache::class);
    
        /* EXECUTE */
        $result = $responseCache->pull($this->targetRouteData);

        /* ASSERT */
        $this->assertSame($response, $result);
    }

    #[Test]
    public function it_returns_null_when_pulling_nonexistent_cache_entry(): void
    {
        /* SETUP */
        $this->mock(ResponseCacheRepository::class, function (MockInterface $mock) {
            $mock->shouldReceive('has')->with($this->key)->once()->andReturn(false);
        });
        $responseCache = app(ResponseCache::class);
    
        /* EXECUTE */
        $result = $responseCache->pull($this->targetRouteData);

        /* ASSERT */
        $this->assertNull($result);
    }

    #[Test]
    public function it_clears_cache_with_tags_successfully(): void
    {
        /* SETUP */
        $sampleTags = ['demon_dextral_horn', 'route_name', 'guest'];
        $this->mock(ResponseCacheRepository::class, function (MockInterface $mock) use ($sampleTags) {
            // Create a separate mock for the tagged cache
            $taggedCacheMock = Mockery::mock(ResponseCacheRepository::class);
            $taggedCacheMock->shouldReceive('clear')
                ->once()
                ->andReturn(true);

            // Mock tags() to return the new instance
            $mock->shouldReceive('tags')
                ->with($sampleTags)
                ->once()
                ->andReturn($taggedCacheMock);
        });
        $responseCache = app(ResponseCache::class);
    
        /* EXECUTE */
        $result = $responseCache->clear($sampleTags);

        /* ASSERT */
        $this->assertTrue($result);
    }

    #[Test]
    public function it_returns_false_and_dispatches_event_when_cache_driver_does_not_support_tags(): void
    {
        /* SETUP */
        Event::fake();
        $tags = ['sample_tag'];
        $this->mock(ResponseCacheRepository::class, function (MockInterface $mock) {
            $mock->shouldReceive('tags')->andThrow(new \BadMethodCallException());
        });
    
        $responseCache = app(ResponseCache::class);
    
        /* EXECUTE */
        $result = $responseCache->clear($tags);
    
        /* ASSERT */
        Event::assertDispatched(TaggedCacheClearFailedEvent::class);
        $this->assertFalse($result);
    }
}