<?php

declare(strict_types=1);

namespace DemonDextralHorn\Handlers;

use DemonDextralHorn\Cache\ResponseCacheValidator;
use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Data\TargetRouteData;
use DemonDextralHorn\Events\NonCacheableResponseEvent;
use DemonDextralHorn\Events\ResponseCachedEvent;
use DemonDextralHorn\Facades\ResponseCache;
use DemonDextralHorn\Resolvers\CookiesResolver;
use DemonDextralHorn\Resolvers\HeadersResolver;
use DemonDextralHorn\Resolvers\QueryParamResolver;
use DemonDextralHorn\Resolvers\RouteParamResolver;
use DemonDextralHorn\Routing\RouteDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * Handles the target route related operations.
 *
 * @class TargetRouteHandler
 */
final class TargetRouteHandler
{
    /**
     * Create a new TargetRouteHandler instance.
     *
     * @param RouteParamResolver $routeParamResolver
     * @param QueryParamResolver $queryParamResolver
     * @param HeadersResolver $headersResolver
     * @param CookiesResolver $cookiesResolver
     * @param RouteDispatcher $routeDispatcher
     * @param ResponseCacheValidator $validator
     */
    public function __construct(
        protected RouteParamResolver $routeParamResolver,
        protected QueryParamResolver $queryParamResolver,
        protected HeadersResolver $headersResolver,
        protected CookiesResolver $cookiesResolver,
        protected RouteDispatcher $routeDispatcher,
        protected ResponseCacheValidator $validator
    ) {}

    /**
     * Handle the target routes for prefetching.
     *
     * @param array $routes
     * @param RequestData $requestData
     * @param ResponseData $responseData
     */
    public function handle(array $routes, RequestData $requestData, ResponseData $responseData): void
    {
        // Iterate over each target route to process and cache their responses.
        foreach ($routes as $targetRoute) {
            $targetRouteName = Arr::get($targetRoute, 'route');
            $targetMethod = Arr::get($targetRoute, 'method', Request::METHOD_GET);
            $targetRouteParams = $this->routeParamResolver->resolve($targetRoute, $requestData, $responseData);
            $targetQueryParams = $this->queryParamResolver->resolve($targetRoute, $requestData, $responseData);
            $targetHeaders = $this->headersResolver->resolve($targetRoute, $requestData, $responseData);
            $targetCookies = $this->cookiesResolver->resolve($targetRoute, $requestData, $responseData);

            // targetRouteParams is normalized in route param resolver, so that can be dispatched directly.
            foreach ($targetRouteParams as $normalizedRouteParams) {
                // Create TargetRouteData instance.
                $targetRouteData = new TargetRouteData(
                    routeName: $targetRouteName,
                    method: $targetMethod,
                    routeParams: $normalizedRouteParams,
                    queryParams: $targetQueryParams,
                    headers: $targetHeaders,
                    cookies: $targetCookies
                );

                // Dispatch the target route and get the response.
                $response = $this->routeDispatcher->dispatch($targetRouteData);

                if ($this->validator->shouldCacheResponse($response)) {
                    ResponseCache::put(
                        $response,
                        $targetRouteData
                    );

                    // Fire event for successful cached response
                    event(new ResponseCachedEvent($response, $targetRouteData));
                } else {
                    // Fire event for non-cacheable response with validation results
                    event(new NonCacheableResponseEvent($response, $this->validator->getValidationResults($response)));
                }
            }
        }
    }
}
