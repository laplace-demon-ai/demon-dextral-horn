<?php

declare(strict_types=1);

namespace Tests\Cache;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Http\Request;
use DemonDextralHorn\Cache\CacheKeyGenerator;
use DemonDextralHorn\Cache\Identifiers\Contracts\UserIdentifierInterface;
use DemonDextralHorn\Data\TargetRouteData;
use DemonDextralHorn\Enums\AuthDriverType;
use DemonDextralHorn\Enums\HttpHeaderType;

/**
 * Test for CacheKeyGenerator.
 * 
 * @class CacheKeyGeneratorTest
 */
final class CacheKeyGeneratorTest extends TestCase
{
    private CacheKeyGenerator $cacheKeyGenerator;
    private UserIdentifierInterface $identifier;

    public function setUp(): void
    {
        parent::setUp();

        config(['demon-dextral-horn.defaults.auth_driver' => AuthDriverType::JWT->value]);
        $this->cacheKeyGenerator = app(CacheKeyGenerator::class);
        $this->identifier = app(UserIdentifierInterface::class);
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
        $userIdentifier = $this->identifier->getIdentifierFor($targetRouteData);

        /* EXECUTE */
        $firstKey = $this->cacheKeyGenerator->generate($targetRouteData, $userIdentifier);
        $secondKey = $this->cacheKeyGenerator->generate($targetRouteData, $userIdentifier);

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
        $firstUserIdentifier = $this->identifier->getIdentifierFor($firstTargetRouteData);
        $secondUserIdentifier = $this->identifier->getIdentifierFor($secondTargetRouteData);

        /* EXECUTE */
        $firstKey = $this->cacheKeyGenerator->generate($firstTargetRouteData, $firstUserIdentifier);
        $secondKey = $this->cacheKeyGenerator->generate($secondTargetRouteData, $secondUserIdentifier);

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
        $firstUserIdentifier = $this->identifier->getIdentifierFor($firstTargetRouteData);
        $secondUserIdentifier = $this->identifier->getIdentifierFor($secondTargetRouteData);

        /* EXECUTE */
        $firstKey = $this->cacheKeyGenerator->generate($firstTargetRouteData, $firstUserIdentifier);
        $secondKey = $this->cacheKeyGenerator->generate($secondTargetRouteData, $secondUserIdentifier);

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
        $firstUserIdentifier = $this->identifier->getIdentifierFor($firstTargetRouteData);
        $secondUserIdentifier = $this->identifier->getIdentifierFor($secondTargetRouteData);

        /* EXECUTE */
        $firstKey = $this->cacheKeyGenerator->generate($firstTargetRouteData, $firstUserIdentifier);
        $secondKey = $this->cacheKeyGenerator->generate($secondTargetRouteData, $secondUserIdentifier);

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
        $identifier = app(UserIdentifierInterface::class);
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
        $firstUserIdentifier = $identifier->getIdentifierFor($firstTargetRouteData);
        $secondUserIdentifier = $identifier->getIdentifierFor($secondTargetRouteData);

        /* EXECUTE */
        $firstKey = $cacheKeyGenerator->generate($firstTargetRouteData, $firstUserIdentifier);
        $secondKey = $cacheKeyGenerator->generate($secondTargetRouteData, $secondUserIdentifier);

        /* ASSERT */
        $this->assertStringContainsString(config('demon-dextral-horn.defaults.cache_prefix') . ':', $firstKey);
        $this->assertStringContainsString(config('demon-dextral-horn.defaults.cache_prefix') . ':', $secondKey);
        $this->assertNotSame($firstKey, $secondKey);
    }
}