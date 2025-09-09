<?php

declare(strict_types=1);

namespace DemonDextralHorn\Cache;

use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;

/**
 * Validates if a response is suitable for caching based on various criteria.
 *
 * @class ResponseCacheValidator
 */
final class ResponseCacheValidator
{
    /**
     * Determine if the response should be cached based on multiple criteria.
     * 
     * @param Response $response
     * 
     * @return bool
     */
    public function shouldCacheResponse(Response $response): bool
    {
        return $this->hasCacheableResponseCode($response)
            && $this->hasCacheableContentType($response)
            && $this->hasReasonableSize($response)
            && ! $this->isStreamingResponse($response);
    }

    /**
     * Get validation results for each caching criterion.
     * 
     * @param Response $response
     * 
     * @return array<string, bool>
     */
    public function getValidationResults(Response $response): array
    {
        return [
            'hasCacheableResponseCode' => $this->hasCacheableResponseCode($response),
            'hasCacheableContentType' => $this->hasCacheableContentType($response),
            'hasReasonableSize' => $this->hasReasonableSize($response),
            'isStreamingResponse' => $this->isStreamingResponse($response),
        ];
    }

    /**
     * Check if the response has a cacheable HTTP status code.
     * Cacheable codes include 200-299 (successful responses) and certain redirects (301, 302, 304).
     * 
     * @param Response $response
     * 
     * @return bool
     */
    private function hasCacheableResponseCode(Response $response): bool
    {
        $cacheableRedirects = [301, 302, 304];

        // Check for successful response codes (200-299).
        if ($response->isSuccessful()) {
            return true;
        }

        // Check for specific cacheable redirect status codes.
        if ($response->isRedirection()) {
            return in_array($response->getStatusCode(), $cacheableRedirects, true);
        }

        return false;
    }

    /**
     * Check if the response has a cacheable Content-Type header.
     * Cacheable types include text/*, application/json, application/xml, etc.
     * 
     * @param Response $response
     * 
     * @return bool
     */
    private function hasCacheableContentType(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');

        if ($contentType === '') {
            return false;
        }

        if (str_starts_with($contentType, 'text/')) {
            return true;
        }

        if (Str::contains($contentType, ['/json', '+json', '/xml', '+xml'])) {
            return true;
        }

        return false;
    }

    /**
     * Check if the response size is within a reasonable limit for caching.
     * This prevents caching excessively large responses that could impact performance.
     * 
     * @param Response $response
     * 
     * @return bool
     */
    private function hasReasonableSize(Response $response): bool
    {
        // Define a maximum size for cacheable responses (e.g., 1MB).
        $maxSizeBytes = config('demon-dextral-horn.defaults.cache_max_size');

        // Prefer Content-Length header when present (no need to load body).
        $contentLength = $response->headers->get('Content-Length');

        if ($contentLength !== null && $contentLength !== '') {
            return ((int) $contentLength) <= $maxSizeBytes;
        }

        $content = $response->getContent();

        // If we cannot get the content, avoid caching.
        if ($content === false) {
            return false;
        }

        return strlen((string) $content) <= $maxSizeBytes;
    }

    /**
     * Check if the response is a streaming response.
     * Streaming responses are typically not cacheable due to their dynamic nature.
     * 
     * @param Response $response
     * 
     * @return bool
     */
    private function isStreamingResponse(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');

        $streamingTypes = [
            'text/event-stream',
            'application/octet-stream',
            'multipart/',
        ];

        // Check if Content-Type indicates a streaming response.
        foreach ($streamingTypes as $type) {
            if (str_contains($contentType, $type)) {
                return true;
            }
        }

        // Check for Transfer-Encoding: chunked header, which indicates streaming.
        if ($response->headers->get('Transfer-Encoding') === 'chunked') {
            return true;
        }

        return false;
    }
}