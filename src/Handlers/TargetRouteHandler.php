<?php

declare(strict_types=1);

namespace DemonDextralHorn\Handlers;

use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
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
     */
    public function __construct(
        protected RouteParamResolver $routeParamResolver,
        protected QueryParamResolver $queryParamResolver,
        protected HeadersResolver $headersResolver,
        protected CookiesResolver $cookiesResolver,
        protected RouteDispatcher $routeDispatcher
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
        // todo Note that when routes are called, in case they return not success message for one of them, it should not block other requests,, - maybe somehow log them but app should work without breaking
        // Iterate over each target route.
        foreach ($routes as $targetRoute) {
            $targetRouteName = Arr::get($targetRoute, 'route');
            $targetMethod = Arr::get($targetRoute, 'method', Request::METHOD_GET);
            $targetRouteParams = $this->routeParamResolver->resolve($targetRoute, $requestData, $responseData);
            $targetQueryParams = $this->queryParamResolver->resolve($targetRoute, $requestData, $responseData);
            $targetHeaders = $this->headersResolver->resolve($targetRoute, $requestData, $responseData);
            $targetCookies = $this->cookiesResolver->resolve($targetRoute, $requestData, $responseData);

            // targetRouteParams is normalized in route param resolver, so that can be dispatched directly.
            foreach ($targetRouteParams as $normalizedRouteParams) {
                // todo respone will be used for caching purpose later, maybe new ResponseCache class will be created and used here,, for each response, it will be cached
                $response = $this->routeDispatcher->dispatch(
                    $targetRouteName,
                    $targetMethod,
                    $normalizedRouteParams,
                    $targetQueryParams,
                    $targetHeaders,
                    $targetCookies
                );
            }
        }
    }
}
