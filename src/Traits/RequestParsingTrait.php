<?php

declare(strict_types=1);

namespace DemonDextralHorn\Traits;

use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Enums\HttpHeaderType;
use DemonDextralHorn\Enums\PrefetchType;

/**
 * Trait for parsing requests and extracting relevant information.
 *
 * @trait RequestParsingTrait
 */
trait RequestParsingTrait
{
    /**
     * Extract the session cookie from the raw cookie string.
     * e.g. 'laravel_session=cookie_value; Path=/; HttpOnly; SameSite=Lax', it will return ['name' => 'laravel_session', 'value' => 'cookie_value']
     *
     * @param string|null $rawCookieString
     *
     * @return array|null
     */
    protected function extractSessionCookieFromString(?string $rawCookieString): ?array
    {
        $cookieName = config('demon-dextral-horn.defaults.session_cookie_name');

        // Cookie header may contain many parts separated by ';'
        foreach (explode(';', $rawCookieString ?? '') as $part) {
            $part = trim($part);

            if ($part === '' || ! str_contains($part, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $part, 2);

            if ($name === $cookieName) {
                // decode if urlencoded (Laravel session can be encoded)
                return ['name' => $name, 'value' => rawurldecode((string) $value)];
            }
        }

        return null;
    }

    /**
     * Normalize route parameters by sorting them by key for deterministic output.
     * e.g. ['id' => 123, 'role' => 3, 'permission' => 6] will remain the same, but ['role' => 3, 'id' => 123] will become ['id' => 123, 'role' => 3]
     *
     * @param array $routeParams
     *
     * @return array
     */
    protected function normalizeRouteParams(array $routeParams): array
    {
        ksort($routeParams);

        // Convert all values to string for consistency, otherwise integers and strings may cause different hashes
        $routeParams = array_map(fn($value) => (string) $value, $routeParams);

        return $routeParams;
    }

    /**
     * Normalize query parameters by sorting them by key, sorting the values of arrays, and converting empty strings to null for deterministic output.
     * e.g. ?filter=latest&page=2&tags[]=php&tags[]=laravel will become ['filter' => 'latest', 'page' => 2, 'tags' => ['laravel', 'php']]
     *
     * @param array $queryParams
     *
     * @return array
     */
    protected function normalizeQueryParams(array $queryParams): array
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
     * Prepare mapped headers array with optional overrides and always include prefetch header.
     *
     * @param RequestData|null $requestData
     * @param array $overrides
     *
     * @return array
     */
    protected function prepareMappedHeaders(?RequestData $requestData, array $overrides = []): array
    {
        $headersData = $requestData?->headers;

        // Map the headers to the correct format as hyphenated capitalization
        $mappedHeaders = [
            HttpHeaderType::AUTHORIZATION->value => $headersData?->authorization,
            HttpHeaderType::ACCEPT->value => $headersData?->accept,
            HttpHeaderType::ACCEPT_LANGUAGE->value => $headersData?->acceptLanguage,
        ];

        // Apply overrides (e.g. resolve custom authorization header)
        $mappedHeaders = array_merge($mappedHeaders, $overrides);

        // Prepare custom prefetch header (Demon-Prefetch-Call)
        $prefetchHeaderName = config('demon-dextral-horn.defaults.prefetch_header');
        $prefetchHeaderValue = PrefetchType::AUTO->value;
        $prefetchHeader = [
            $prefetchHeaderName => $prefetchHeaderValue,
        ];

        // Filter out nulls
        return array_filter(
            array_merge($mappedHeaders, $prefetchHeader),
            fn ($value) => ! is_null($value)
        );
    }

    /**
     * Prepare cookies array.
     *
     * @param RequestData|null $requestData
     * @param array $overrides
     *
     * @return array
     */
    protected function prepareCookies(?RequestData $requestData, array $overrides = []): array
    {
        $cookiesData = $requestData?->cookies;

        // Auto forward session cookie if exists
        $cookies = [
            'session_cookie' => $cookiesData?->sessionCookie,
        ];

        // Apply overrides (e.g. resolved session cookie). Resolved cookies have priority over request cookies.
        foreach ($overrides as $key => $value) {
            if ($value !== null) {
                $cookies[$key] = $value;
            }
        }

        return array_filter(
            $cookies,
            fn ($value) => ! is_null($value)
        );
    }
}
