<?php

declare(strict_types=1);

namespace Tests\Cache;

use Tests\TestCase;
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

#[CoversClass(ResponseCache::class)]
final class ResponseCacheTest extends TestCase
{
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
}