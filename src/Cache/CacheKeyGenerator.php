<?php

declare(strict_types=1);

namespace DemonDextralHorn\Cache;

use DemonDextralHorn\Data\TargetRouteData;
use DemonDextralHorn\Enums\HttpHeaderType;
use DemonDextralHorn\Traits\RequestParsingTrait;
use Illuminate\Support\Arr;

/**
 * Generates a unique cache key for storing and retrieving cached responses based on various request attributes.
 *
 * @class CacheKeyGenerator
 */
final class CacheKeyGenerator
{
    use RequestParsingTrait;

    // xxh128 is fast non-cryptographic hash, suitable for cache keys
    public const HASH_ALGORITHM = 'xxh128';

    /**
     * Generate a unique cache key based on the target route data (by adding user identifier for user specific caching).
     *
     * @param TargetRouteData $targetRouteData
     * @param string $userIdentifier
     *
     * @return string
     */
    public function generate(TargetRouteData $targetRouteData, string $userIdentifier): string
    {
        $acceptLanguage = Arr::get($targetRouteData->headers, HttpHeaderType::ACCEPT_LANGUAGE->value);

        $keyComponents = [
            'user_identifier' => $userIdentifier, // e.g. jwt:<token>
            'route_name' => $targetRouteData->routeName, // e.g. user.profile
            'method' => strtolower($targetRouteData->method), // e.g. get -> lowercase for deterministic output
            'route_params' => $this->normalizeRouteParams($targetRouteData->routeParams), // e.g. ['id' => 123, 'role' => 3, 'permission' => 6]
            'query_params' => $this->normalizeQueryParams($targetRouteData->queryParams), // e.g. ['filter' => 'latest', 'page' => 2, 'tags' => ['php', 'laravel']]
            'accept_language' => $acceptLanguage, // e.g. en-US,en;q=0.5
        ];

        // We will get something like demon_dextral_horn:hashed_string
        return config('demon-dextral-horn.defaults.prefetch_prefix') . ':' . $this->generateHash($keyComponents);
    }

    /**
     * Generate a hash for the cache key components.
     *
     * @param array $keyComponents
     *
     * @return string
     */
    private function generateHash(array $keyComponents): string
    {
        $jsonKeyComponents = json_encode($keyComponents, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return hash(self::HASH_ALGORITHM, $jsonKeyComponents);
    }
}
