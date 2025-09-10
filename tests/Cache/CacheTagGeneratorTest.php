<?php

declare(strict_types=1);

namespace Tests\Cache;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Illuminate\Http\Request;
use DemonDextralHorn\Enums\HttpHeaderType;
use DemonDextralHorn\Cache\CacheTagGenerator;
use DemonDextralHorn\Data\TargetRouteData;
use DemonDextralHorn\Cache\Identifiers\Contracts\UserIdentifierInterface;
use DemonDextralHorn\Enums\AuthDriverType;

#[CoversClass(CacheTagGenerator::class)]
final class CacheTagGeneratorTest extends TestCase
{
    private CacheTagGenerator $cacheTagGenerator;
    private string $defaultPrefix;
    private UserIdentifierInterface $identifier;

    public function setUp(): void
    {
        parent::setUp();

        $this->cacheTagGenerator = app(CacheTagGenerator::class);
        $this->defaultPrefix = config('demon-dextral-horn.defaults.cache_prefix');
        config(['demon-dextral-horn.defaults.auth_driver' => AuthDriverType::JWT->value]);
        $this->identifier = app(UserIdentifierInterface::class);
    }

    #[Test]
    public function it_generates_tags_correctly(): void
    {
        /* SETUP */
        $targetRouteData = new TargetRouteData(
            routeName: 'sample.route',
            method: Request::METHOD_GET,
            routeParams: ['id' => 2],
            queryParams: ['page' => 3],
            headers: [
                HttpHeaderType::ACCEPT_LANGUAGE->value => 'en-US,en;q=0.5',
                HttpHeaderType::AUTHORIZATION->value => 'Bearer sample_token',
            ],
            cookies: []
        );
        $userIdentifier = $this->identifier->getIdentifierFor($targetRouteData); // e.g. "jwt:69d56883175b26451297e...

        /* EXECUTE */
        $tags = $this->cacheTagGenerator->generate($targetRouteData, $userIdentifier);

        /* ASSERT */
        $this->assertIsArray($tags);
        $this->assertContains($this->defaultPrefix, $tags);
        $this->assertContains('sample_route', $tags);
        $jwtItems = array_filter($tags, fn($item) => str_contains($item, 'jwt_'));
        $this->assertNotEmpty($jwtItems);
    }

    #[Test]
    public function it_handles_empty_route_and_user_identifier(): void
    {
        /* SETUP */
        $targetRouteData = new TargetRouteData(
            routeName: '',
            method: Request::METHOD_GET,
            routeParams: [],
            queryParams: [],
            headers: [],
            cookies: []
        );
        $userIdentifier = '';
    
        /* EXECUTE */
        $tags = $this->cacheTagGenerator->generate($targetRouteData, $userIdentifier);
    
        /* ASSERT */
        $this->assertIsArray($tags);
        $this->assertContains($this->defaultPrefix, $tags);
        $this->assertContains($this->cacheTagGenerator::UNNAMED_TAG, $tags);
        $this->assertCount(2, $tags);
    }

    #[Test]
    public function it_removes_duplicate_tags(): void
    {
        /* SETUP */
        $targetRouteData = new TargetRouteData(
            routeName: 'jwt_abc123',
            method: Request::METHOD_GET,
            routeParams: [],
            queryParams: [],
            headers: [],
            cookies: []
        );
        $userIdentifier = 'JWT_ABC123';
    
        /* EXECUTE */
        $tags = $this->cacheTagGenerator->generate($targetRouteData, $userIdentifier);
    
        /* ASSERT */
        $this->assertEquals([$this->defaultPrefix, 'jwt_abc123'], $tags);
    }

    #[Test]
    public function it_normalizes_tags(): void
    {
        /* SETUP */
        $targetRouteData = new TargetRouteData(
            routeName: 'User.Profile@123!',
            method: Request::METHOD_GET,
            routeParams: [],
            queryParams: [],
            headers: [],
            cookies: []
        );
        $userIdentifier = 'JWT:ABC 123';
    
        /* EXECUTE */
        $tags = $this->cacheTagGenerator->generate($targetRouteData, $userIdentifier);
    
        /* ASSERT */
        $this->assertContains('user_profile_123', $tags);
        $this->assertContains('jwt_abc_123', $tags);
    }
}
