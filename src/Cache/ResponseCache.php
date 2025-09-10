<?php

declare(strict_types=1);

namespace DemonDextralHorn\Cache;

use BadMethodCallException;
use DemonDextralHorn\Cache\Identifiers\Contracts\UserIdentifierInterface;
use DemonDextralHorn\Data\TargetRouteData;
use Spatie\ResponseCache\ResponseCacheRepository;
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
     * @param UserIdentifierInterface $identifier
     * @param CacheKeyGenerator $cacheKeyGenerator
     * @param CacheTagGenerator $cacheTagGenerator
     */
    public function __construct(
        protected ResponseCacheRepository $cache,
        protected UserIdentifierInterface $identifier,
        protected CacheKeyGenerator $cacheKeyGenerator,
        protected CacheTagGenerator $cacheTagGenerator
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
        $key = $this->getCacheKey($targetRouteData);
        $taggedCache = $this->getTaggedCache($targetRouteData);

        $taggedCache->put(
            key: $key,
            response: $response,
            seconds: config('demon-dextral-horn.defaults.cache_ttl')
        );
    }

    /**
     * Generate a unique cache key based on the target route data and user identifier.
     *
     * @param TargetRouteData $targetRouteData
     *
     * @return string
     */
    private function getCacheKey(TargetRouteData $targetRouteData): string
    {
        // Get the user identifier
        $userIdentifier = $this->getUserIdentifier($targetRouteData);

        return $this->cacheKeyGenerator->generate($targetRouteData, $userIdentifier);
    }

    /**
     * Get a tagged cache repository based on the target route data and user identifier.
     * If the cache driver does not support tags, return the untagged repository.
     *
     * @param TargetRouteData $targetRouteData
     *
     * @return ResponseCacheRepository
     */
    private function getTaggedCache(TargetRouteData $targetRouteData): ResponseCacheRepository
    {
        // Get the user identifier
        $userIdentifier = $this->getUserIdentifier($targetRouteData);

        // Generate tags based on route name and user identifier
        $allTags = $this->cacheTagGenerator->generate($targetRouteData, $userIdentifier);

        if (empty($allTags)) {
            return $this->cache;
        }

        try {
            return $this->cache->tags($allTags);
        } catch (BadMethodCallException $e) {
            // Cache driver doesn't support tags - fallback to untagged cache. (redis and memcached support tags, file and database do not)
            return $this->cache;
        }
    }

    /**
     * Get the user identifier string for the given target route data.
     *
     * @param TargetRouteData $targetRouteData
     *
     * @return string
     */
    private function getUserIdentifier(TargetRouteData $targetRouteData): string
    {
        return $this->identifier->getIdentifierFor($targetRouteData);
    }
}
