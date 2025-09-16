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
use DemonDextralHorn\Enums\AuthDriverType;

#[CoversClass(CacheTagGenerator::class)]
final class CacheTagGeneratorTest extends TestCase
{
    private CacheTagGenerator $cacheTagGenerator;
    private string $defaultPrefix;

    public function setUp(): void
    {
        parent::setUp();

        $this->cacheTagGenerator = app(CacheTagGenerator::class);
        $this->defaultPrefix = config('demon-dextral-horn.defaults.prefetch_prefix');
        config(['demon-dextral-horn.defaults.auth_driver' => AuthDriverType::JWT->value]);
    }

    /**
     * Add the default prefix to a tag.
     * 
     * @param string $tag
     *
     * @return string
     */
    private function addDefaultPrefix(string $tag): string
    {
        return $this->defaultPrefix . ':' . $tag;
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

        /* EXECUTE */
        $tags = $this->cacheTagGenerator->generate($targetRouteData);

        /* ASSERT */
        $this->assertIsArray($tags);
        $this->assertContains($this->defaultPrefix, $tags);
        $this->assertContains($this->addDefaultPrefix('sample_route'), $tags);
    }

    #[Test]
    public function it_handles_empty_route_set_unnamed_tag(): void
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
    
        /* EXECUTE */
        $tags = $this->cacheTagGenerator->generate($targetRouteData);
    
        /* ASSERT */
        $this->assertIsArray($tags);
        $this->assertContains($this->defaultPrefix, $tags);
        $this->assertContains($this->addDefaultPrefix($this->cacheTagGenerator::UNNAMED_TAG), $tags);
        $this->assertCount(2, $tags);
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
    
        /* EXECUTE */
        $tags = $this->cacheTagGenerator->generate($targetRouteData);
    
        /* ASSERT */
        $this->assertContains($this->addDefaultPrefix('user_profile_123'), $tags);
    }
}
