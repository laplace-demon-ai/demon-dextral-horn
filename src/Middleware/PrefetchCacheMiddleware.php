<?php

declare(strict_types=1);

namespace DemonDextralHorn\Middleware;

use Closure;
use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\TargetRouteData;
use DemonDextralHorn\Facades\ResponseCache;
use DemonDextralHorn\Traits\RequestParsingTrait;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to handle cached responses for target routes before processing the actual request.
 *
 * @class PrefetchCacheMiddleware
 */
final class PrefetchCacheMiddleware
{
    use RequestParsingTrait;

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $enabled = config('demon-dextral-horn.defaults.enabled');

        if ($enabled) {
            // first we need to create RequestData from the request
            $requestData = RequestData::fromRequest($request);

            $targetRouteData = new TargetRouteData(
                routeName: $requestData->routeName,
                method: $requestData->method,
                routeParams: $this->normalizeRouteParams($requestData->routeParams ?? []),
                queryParams: $this->normalizeQueryParams($requestData->queryParams ?? []),
                headers: $this->prepareMappedHeaders($requestData),
                cookies: $this->prepareCookies($requestData)
            );

            if (ResponseCache::has($targetRouteData)) {
                // Retrieve and remove the cached response (pull) in order to invalidate it after it is used.
                $cachedResponse = ResponseCache::pull($targetRouteData);

                if ($cachedResponse !== null) {
                    return $cachedResponse;
                }
            }
        }

        // Proceed with the request if no cached response is found
        return $next($request);
    }
}
