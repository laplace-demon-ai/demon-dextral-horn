<?php

declare(strict_types=1);

namespace Tests\Cache;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Illuminate\Http\Request;
use DemonDextralHorn\Cache\CacheKeyGenerator;
use DemonDextralHorn\Cache\Identifiers\GuestUserIdentifier;
use DemonDextralHorn\Data\TargetRouteData;
use DemonDextralHorn\Enums\AuthDriverType;
use DemonDextralHorn\Enums\HttpHeaderType;
use DemonDextralHorn\Cache\Identifiers\JwtUserIdentifier;
use DemonDextralHorn\Cache\Identifiers\SessionUserIdentifier;

/**
 * Test for CacheKeyGenerator.
 * 
 * @class CacheKeyGeneratorTest
 */
final class CacheKeyGeneratorTest extends TestCase
{
    private CacheKeyGenerator $cacheKeyGenerator;

    public function setUp(): void
    {
        parent::setUp();

        config(['demon-dextral-horn.defaults.auth_driver' => AuthDriverType::JWT->value]);
        $this->cacheKeyGenerator = app(CacheKeyGenerator::class);
    }

    #[Test]
    public function it_successfully_creates_jwt_user_identifier_based_on_config(): void
    {
        /* SETUP */
        config(['demon-dextral-horn.defaults.auth_driver' => AuthDriverType::JWT->value]);
        $cacheKeyGenerator = app(CacheKeyGenerator::class);
        $reflection = new ReflectionClass($cacheKeyGenerator);
        $property = $reflection->getProperty('identifier');

        /* EXECUTE */
        $identifier = $property->getValue($cacheKeyGenerator);
        
        /* ASSERT */
        $this->assertInstanceOf(JwtUserIdentifier::class, $identifier);
    }

    #[Test]
    public function it_successfully_creates_session_user_identifier_based_on_config(): void
    {
        /* SETUP */
        config(['demon-dextral-horn.defaults.auth_driver' => AuthDriverType::SESSION->value]);
        $cacheKeyGenerator = app(CacheKeyGenerator::class);
        $reflection = new ReflectionClass($cacheKeyGenerator);
        $property = $reflection->getProperty('identifier');

        /* EXECUTE */
        $identifier = $property->getValue($cacheKeyGenerator);
        
        /* ASSERT */
        $this->assertInstanceOf(SessionUserIdentifier::class, $identifier);
    }

    #[Test]
    public function it_successfully_creates_guest_user_identifier_based_on_config_as_fallback(): void
    {
        /* SETUP */
        config(['demon-dextral-horn.defaults.auth_driver' => null]);
        $cacheKeyGenerator = app(CacheKeyGenerator::class);
        $reflection = new ReflectionClass($cacheKeyGenerator);
        $property = $reflection->getProperty('identifier');

        /* EXECUTE */
        $identifier = $property->getValue($cacheKeyGenerator);
        
        /* ASSERT */
        $this->assertInstanceOf(GuestUserIdentifier::class, $identifier);
    }

    #[Test]
    public function it_generates_deterministic_cache_key(): void
    {
        /* SETUP */
        $targetRouteData = new TargetRouteData(
            routeName: 'sample.route',
            method: Request::METHOD_GET,
            routeParams: ['id' => 2, 'role' => 1],
            queryParams: ['page' => 1, 'filter' => 'latest'],
            headers: [HttpHeaderType::ACCEPT_LANGUAGE->value => 'en-US,en;q=0.5'],
            cookies: []
        );

        /* EXECUTE */
        $firstKey = $this->cacheKeyGenerator->generate($targetRouteData);
        $secondKey = $this->cacheKeyGenerator->generate($targetRouteData);

        /* ASSERT */
        $this->assertStringContainsString(config('demon-dextral-horn.defaults.cache_prefix') . ':', $firstKey);
        $this->assertStringContainsString(config('demon-dextral-horn.defaults.cache_prefix') . ':', $secondKey);
        $this->assertSame($firstKey, $secondKey);
    }

    #[Test]
    public function it_normalizes_query_params_and_route_params_and_gives_same_cache_key(): void
    {
        /* SETUP */
        $firstTargetRouteData = new TargetRouteData(
            routeName: 'sample.route',
            method: Request::METHOD_GET,
            routeParams: ['role' => 1, 'id' => 2],
            queryParams: ['filter' => 'latest', 'page' => ''],
            headers: [HttpHeaderType::ACCEPT_LANGUAGE->value => 'en-US,en;q=0.5'],
            cookies: []
        );
        $secondTargetRouteData = new TargetRouteData(
            routeName: 'sample.route',
            method: Request::METHOD_GET,
            routeParams: ['id' => 2, 'role' => 1], // order swapped
            queryParams: ['page' => null, 'filter' => 'latest'], // '' should normalize to null
            headers: [HttpHeaderType::ACCEPT_LANGUAGE->value => 'en-US,en;q=0.5'],
            cookies: []
        );

        /* EXECUTE */
        $firstKey = $this->cacheKeyGenerator->generate($firstTargetRouteData);
        $secondKey = $this->cacheKeyGenerator->generate($secondTargetRouteData);

        /* ASSERT */
        $this->assertSame($firstKey, $secondKey);
    }

    #[Test]
    public function it_normalizes_array_query_params_and_gives_same_cache_key(): void
    {
        /* SETUP */
        $firstTargetRouteData = new TargetRouteData(
            routeName: 'sample.route',
            method: Request::METHOD_GET,
            routeParams: ['id' => 2],
            queryParams: ['tags' => ['php', 'laravel', 'another'], 'page' => 1],
            headers: [HttpHeaderType::ACCEPT_LANGUAGE->value => 'en-US,en;q=0.5'],
            cookies: []
        );
        $secondTargetRouteData = new TargetRouteData(
            routeName: 'sample.route',
            method: Request::METHOD_GET,
            routeParams: ['id' => 2],
            queryParams: ['page' => 1, 'tags' => ['laravel', 'another', 'php']], // order swapped in tags array
            headers: [HttpHeaderType::ACCEPT_LANGUAGE->value => 'en-US,en;q=0.5'],
            cookies: []
        );

        /* EXECUTE */
        $firstKey = $this->cacheKeyGenerator->generate($firstTargetRouteData);
        $secondKey = $this->cacheKeyGenerator->generate($secondTargetRouteData);

        /* ASSERT */
        $this->assertSame($firstKey, $secondKey);
    }

    #[Test]
    public function it_includes_authorization_header_and_gives_different_key_for_different_token(): void
    {
        /* SETUP */
        config(['demon-dextral-horn.defaults.auth_driver' => AuthDriverType::JWT->value]);
        $firstTargetRouteData = new TargetRouteData(
            routeName: 'sample.route',
            method: Request::METHOD_GET,
            routeParams: ['id' => 2],
            queryParams: ['page' => 1],
            headers: [
                HttpHeaderType::ACCEPT_LANGUAGE->value => 'en-US,en;q=0.5',
                HttpHeaderType::AUTHORIZATION->value => 'Bearer sample.jwt.token'
            ],
            cookies: []
        );
        $secondTargetRouteData = new TargetRouteData(
            routeName: 'sample.route',
            method: Request::METHOD_GET,
            routeParams: ['id' => 2],
            queryParams: ['page' => 1],
            headers: [
                HttpHeaderType::ACCEPT_LANGUAGE->value => 'en-US,en;q=0.5',
                HttpHeaderType::AUTHORIZATION->value => 'Bearer another.jwt.token' // different token
            ],
            cookies: []
        );

        /* EXECUTE */
        $firstKey = $this->cacheKeyGenerator->generate($firstTargetRouteData);
        $secondKey = $this->cacheKeyGenerator->generate($secondTargetRouteData);

        /* ASSERT */
        $this->assertStringContainsString(config('demon-dextral-horn.defaults.cache_prefix') . ':', $firstKey);
        $this->assertStringContainsString(config('demon-dextral-horn.defaults.cache_prefix') . ':', $secondKey);
        $this->assertNotSame($firstKey, $secondKey);
    }

    #[Test]
    public function it_includes_session_cookie_and_gives_different_key_for_different_session(): void
    {
        /* SETUP */
        config(['demon-dextral-horn.defaults.auth_driver' => AuthDriverType::SESSION->value]);
        $cacheKeyGenerator = app(CacheKeyGenerator::class);
        $firstTargetRouteData = new TargetRouteData(
            routeName: 'sample.route',
            method: Request::METHOD_GET,
            routeParams: ['id' => 2],
            queryParams: ['page' => 1],
            headers: [HttpHeaderType::ACCEPT_LANGUAGE->value => 'en-US,en;q=0.5'],
            cookies: ['session_cookie' => config('demon-dextral-horn.defaults.session_cookie_name') . '=first_session_cookie_value; Path=/; HttpOnly; SameSite=Lax']
        );
        $secondTargetRouteData = new TargetRouteData(
            routeName: 'sample.route',
            method: Request::METHOD_GET,
            routeParams: ['id' => 2],
            queryParams: ['page' => 1],
            headers: [HttpHeaderType::ACCEPT_LANGUAGE->value => 'en-US,en;q=0.5'],
            cookies: ['session_cookie' => config('demon-dextral-horn.defaults.session_cookie_name') . '=second_session_cookie_value; Path=/; HttpOnly; SameSite=Lax'] // different session cookie
        );

        /* EXECUTE */
        $firstKey = $cacheKeyGenerator->generate($firstTargetRouteData);
        $secondKey = $cacheKeyGenerator->generate($secondTargetRouteData);

        /* ASSERT */
        $this->assertStringContainsString(config('demon-dextral-horn.defaults.cache_prefix') . ':', $firstKey);
        $this->assertStringContainsString(config('demon-dextral-horn.defaults.cache_prefix') . ':', $secondKey);
        $this->assertNotSame($firstKey, $secondKey);
    }
}