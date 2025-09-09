<?php

declare(strict_types=1);

namespace DemonDextralHorn\Cache;

use Spatie\ResponseCache\ResponseCacheRepository;
use DemonDextralHorn\Data\TargetRouteData;
use Symfony\Component\HttpFoundation\Response;

/**
 * Caching mechanism for the target routes responses (wrapping/using Spatie's ResponseCache)
 *
 * @class ResponseCache
 */
final class ResponseCache
{
    /**
     * Create a new ResponseCache instance with the injected ResponseCacheRepository (spatie response cache)
     *
     * @param ResponseCacheRepository $cache
     * @param CacheKeyGenerator $cacheKeyGenerator
     */
    public function __construct(
        protected ResponseCacheRepository $cache,
        protected CacheKeyGenerator $cacheKeyGenerator
    ) {}

    /**
     * Store the response in the cache with a unique key generated from the target route data.
     * Use ResponseCacheRepository to store the response.
     * 
     * @param Response $response
     * @param TargetRouteData $targetRouteData
     * 
     * @return void
     */
    public function put(Response $response, TargetRouteData $targetRouteData): void
    {
        $key = $this->cacheKeyGenerator->generate($targetRouteData);
        
        $this->cache->put(
            key: $key,
            response: $response,
            seconds: config('demon-dextral-horn.defaults.cache_ttl')
        );
    }
}