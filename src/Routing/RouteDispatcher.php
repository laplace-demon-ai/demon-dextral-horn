<?php

declare(strict_types=1);

namespace DemonDextralHorn\Routing;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Dispatches a request with the given route information/parameters.
 *
 * @class RouteDispatcher
 */
final class RouteDispatcher
{
    /**
     * Dispatch the request to the specified route.
     *
     * @param string $routeName
     * @param string $method
     * @param array $routeParams
     * @param array $queryParams
     * @param array $headers
     * 
     * @return Response
     */
    public function dispatch(
        string $routeName,
        string $method,
        array $routeParams,
        array $queryParams,
        array $headers
    ): Response {
        // Generate url from route name and parameters (e.g. "/89/69/sample-target-route-success/21/30")
        $url = route($routeName, $routeParams, false);

        // Add query parameters to the url
        $url .= '?' . http_build_query($queryParams);

        $request = Request::create($url, $method, [], [], [], []); // todo we might have server (HTTP_AUTHORIZATION etc) and cookies,, so adding all of them to method might not be the best practice?

        $request->headers->replace($headers);

        return app('router')->dispatch($request);
    }
}