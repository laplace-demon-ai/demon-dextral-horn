<?php

declare(strict_types=1);

namespace DemonDextralHorn\Handlers;

use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Resolvers\RouteParamResolver;
use DemonDextralHorn\Resolvers\QueryParamResolver;
use DemonDextralHorn\Resolvers\HeadersResolver;
use DemonDextralHorn\Routing\RouteDispatcher;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;

/**
 * Handles the target route related operations.
 *
 * @class TargetRouteHandler
 */
final class TargetRouteHandler
{
    /**
     * Create a new handler instance.
     *
     * @param RouteParamResolver $routeParamResolver
     * @param QueryParamResolver $queryParamResolver
     * @param HeadersResolver $headersResolver
     * @param RouteDispatcher $routeDispatcher
     */
    public function __construct(
        protected RouteParamResolver $routeParamResolver,
        protected QueryParamResolver $queryParamResolver,
        protected HeadersResolver $headersResolver,
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
        // Iterate over each target route.
        foreach ($routes as $targetRoute) {
            $targetRouteName = Arr::get($targetRoute, 'route');
            $targetMethod = Arr::get($targetRoute, 'method', Request::METHOD_GET);
            $targetRouteParams = $this->routeParamResolver->resolve($targetRoute, $requestData, $responseData);
            $targetQueryParams = $this->queryParamResolver->resolve($targetRoute, $requestData, $responseData);
            $targetHeaders = $this->headersResolver->resolve($targetRoute, $requestData, $responseData);

            // Dispatch the route and get the response
            $response = $this->routeDispatcher->dispatch(
                $targetRouteName, 
                $targetMethod, 
                $targetRouteParams, 
                $targetQueryParams, 
                $targetHeaders
            );
            // todo respone will be used for caching purpose later, maybe new ResponseCache class will be created and used here
        }
    }
}