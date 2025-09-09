<?php

declare(strict_types=1);

namespace DemonDextralHorn\Cache;

use DemonDextralHorn\Cache\Identifiers\Contracts\UserIdentifierInterface;
use DemonDextralHorn\Data\TargetRouteData;
use DemonDextralHorn\Enums\HttpHeaderType;
use Illuminate\Support\Arr;

/**
 * Generates a unique cache key for storing and retrieving cached responses based on various request attributes.
 *
 * @class CacheKeyGenerator
 */
final class CacheKeyGenerator
{
    // xxh128 is fast non-cryptographic hash, suitable for cache keys
    public const HASH_ALGORITHM = 'xxh128';

    /**
     * Create a new CacheKeyGenerator instance.
     *
     * @param UserIdentifierInterface $identifier
     */
    public function __construct(
        protected UserIdentifierInterface $identifier
    ) {}

    /**
     * Generate a unique cache key based on the target route data (by adding user identifier for user specific caching).
     * 
     * @param TargetRouteData $targetRouteData
     * 
     * @return string
     */
    public function generate(TargetRouteData $targetRouteData): string
    {
        $userIdentifier = $this->identifier->getIdentifierFor($targetRouteData);

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
        return config('demon-dextral-horn.defaults.cache_prefix') . ':' . $this->generateHash($keyComponents);
    }

    /**
     * Normalize route parameters by sorting them by key for deterministic output.
     * 
     * @param array $routeParams
     * 
     * @return array
     */
    private function normalizeRouteParams(array $routeParams): array
    {
        ksort($routeParams);

        return $routeParams;
    }

    /**
     * Normalize query parameters by sorting them by key, sorting the values of arrays, and converting empty strings to null for deterministic output.
     * 
     * @param array $queryParams
     * 
     * @return array
     */
    private function normalizeQueryParams(array $queryParams): array
    {
        // Recursive anonymous function to normalize the array
        $normalize = function (&$array) use (&$normalize) {
            if (array_is_list($array)) {
                sort($array); // sort values for lists
            } else {
                ksort($array); // sort by keys for associative arrays
            }

            foreach ($array as &$value) {
                if (is_array($value)) {
                    $normalize($value);
                } elseif ($value === '') {
                    $value = null;
                }
            }

            // We unset the reference to avoid potential side effects
            unset($value);
        };

        $normalize($queryParams);

        return $queryParams;
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
        $json = json_encode($keyComponents, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return hash(self::HASH_ALGORITHM, $json);
    }
}