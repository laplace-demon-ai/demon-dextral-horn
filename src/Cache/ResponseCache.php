<?php

declare(strict_types=1);

namespace DemonDextralHorn\Cache;

use BadMethodCallException;
use DemonDextralHorn\Cache\Identifiers\Contracts\UserIdentifierInterface;
use DemonDextralHorn\Data\TargetRouteData;
use DemonDextralHorn\Events\TaggedCacheClearFailedEvent;
use Spatie\ResponseCache\ResponseCacheRepository;
use Symfony\Component\HttpFoundation\Response;

/**
 * Caching mechanism for the target routes responses (wrapping/using Spatie's ResponseCache)
 *
 * @class ResponseCache
 */
class ResponseCache
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
     * Check if a cached response exists for the given target route data.
     *
     * @param TargetRouteData $targetRouteData
     *
     * @return bool
     */
    public function has(TargetRouteData $targetRouteData): bool
    {
        $key = $this->getCacheKey($targetRouteData);

        return $this->cache->has($key);
    }

    /**
     * Retrieve the cached response for the given target route data.
     *
     * @param TargetRouteData $targetRouteData
     *
     * @return Response|null
     */
    public function get(TargetRouteData $targetRouteData): ?Response
    {
        $key = $this->getCacheKey($targetRouteData);

        return $this->cache->get($key);
    }

    /**
     * Remove the cached response for the given target route data (cache key is generted from the target route data)
     *
     * @param TargetRouteData $targetRouteData
     *
     * @return bool Whether the cache entry was successfully removed.
     */
    public function forget(TargetRouteData $targetRouteData): bool
    {
        $key = $this->getCacheKey($targetRouteData);

        return $this->cache->forget($key);
    }

    /**
     * Clear the cached responses for the given tags (do NOT clear the whole cache if tags are not supported or no tags provided).
     *
     * @param array|null $tags
     *
     * @return bool
     */
    public function clear(?array $tags = []): bool
    {
        $defaultTag = config('demon-dextral-horn.defaults.prefetch_prefix');

        // Remove duplicates and empty values, ensure default tag is always included
        $allTags = array_values(array_unique(array_filter(array_merge([$defaultTag], $tags))));

        // Get a tagged cache repository by associating the tags to the cache
        $taggedCache = $this->associateTagsToCache($allTags);

        // If the tagged cache is the same as the untagged cache, the driver doesn't support tags, so we fire an event and return false
        if ($taggedCache === $this->cache) {
            event(new TaggedCacheClearFailedEvent($allTags));

            // Driver doesn't support tags, do NOT clear the whole cache - return false
            return false;
        }

        return $taggedCache->clear();
    }

    /**
     * Retrieve and remove the cached response for the given target route data.
     *
     * @param TargetRouteData $targetRouteData
     *
     * @return Response|null
     */
    public function pull(TargetRouteData $targetRouteData): ?Response
    {
        $key = $this->getCacheKey($targetRouteData);

        // Check if the key exists before attempting to get and forget
        if (! $this->cache->has($key)) {
            return null;
        }

        // Retrieve the response
        $response = $this->cache->get($key);

        // Remove it from the cache
        $this->cache->forget($key);

        return $response;
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
        $userIdentifier = $this->identifier->getIdentifierFor($targetRouteData);

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
        $userIdentifier = $this->identifier->getIdentifierFor($targetRouteData);

        // Generate tags based on route name and user identifier
        $allTags = $this->cacheTagGenerator->generate($targetRouteData, $userIdentifier);

        return $this->associateTagsToCache($allTags);
    }

    /**
     * Attempt to associate the given tags to the cache repository.
     * If the cache driver does not support tags, return the untagged repository.
     * (redis and memcached support tags, file and database do not)
     *
     * @param array|null $tags
     *
     * @return ResponseCacheRepository
     */
    private function associateTagsToCache(?array $tags)
    {
        try {
            return $this->cache->tags($tags);
        } catch (BadMethodCallException $e) {
            // Cache driver doesn't support tags - fallback to untagged cache.
            return $this->cache;
        }
    }
}
