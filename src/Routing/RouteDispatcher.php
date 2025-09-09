<?php

declare(strict_types=1);

namespace DemonDextralHorn\Routing;

use DemonDextralHorn\Data\TargetRouteData;
use DemonDextralHorn\Enums\HttpHeaderType;
use DemonDextralHorn\Enums\HttpServerVariableType;
use DemonDextralHorn\Events\RouteDispatchFailedEvent;
use DemonDextralHorn\Traits\RequestParsingTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Dispatches a request with the given route information/parameters.
 *
 * @class RouteDispatcher
 */
final class RouteDispatcher
{
    use RequestParsingTrait;

    /**
     * Dispatch the request to the specified route.
     *
     * @param TargetRouteData $targetRouteData
     *
     * @return Response|null
     */
    public function dispatch(
        TargetRouteData $targetRouteData
    ): ?Response {
        $url = $this->prepareUrl($targetRouteData->routeName, $targetRouteData->routeParams, $targetRouteData->queryParams);

        $request = Request::create(
            uri: $url,
            method: $targetRouteData->method,
        );

        $request = $this->prepareRequest($request, $targetRouteData->headers, $targetRouteData->cookies);

        $originalRequest = app('request');

        try {
            // Bind the request to the application, when request() is called it will return this request instance with proper headers, cookies, etc.
            app()->instance('request', $request);

            // Dispatch the request with the router
            return app('router')->dispatch($request);
        
        } catch (Throwable $exception) {
            // Fire an event when dispatching fails - gives more control to the user to log or handle the exception without breaking the main app flow
            event(new RouteDispatchFailedEvent($exception, $targetRouteData));

            // Return default response in case of an error
            return new Response('Internal Server Error', 500);
        } finally {
            // Restore the original request instance, preventing any side effects
            app()->instance('request', $originalRequest);
        }
    }

    /**
     * Prepare the URL for the request with the given route name, route parameters, and query parameters.
     *
     * @param string $routeName
     * @param array $routeParams
     * @param array $queryParams
     *
     * @return string
     */
    private function prepareUrl(string $routeName, array $routeParams, array $queryParams): string
    {
        // Generate url from route name and parameters (e.g. "/89/69/sample-target-route-success/21/30")
        $url = route($routeName, $routeParams, false);

        if (! empty($queryParams)) {
            // Add query parameters to the url
            $url .= '?' . http_build_query($queryParams);
        }

        return $url;
    }

    /**
     * Prepare the request with the given headers and cookies.
     *
     * @param Request $request
     * @param array $headers
     * @param array $cookies
     *
     * @return Request
     */
    private function prepareRequest(Request $request, array $headers, array $cookies): Request
    {
        /**
         * Replace headers with the provided headers.
         * Note! It erases any existing headers, so it needs to be used before any other header manipulations.
         */
        $request->headers->replace($headers);

        // Add server variable for authorization for JWT
        if (Arr::has($headers, HttpHeaderType::AUTHORIZATION->value)) {
            $request->server->set(
                HttpServerVariableType::AUTHORIZATION->value,
                Arr::get($headers, HttpHeaderType::AUTHORIZATION->value)
            );
        }

        if (Arr::has($cookies, 'session_cookie')) {
            // add some raw sample to comment for laravel session
            $rawSessionCookie = Arr::get($cookies, 'session_cookie'); // e.g. 'laravel_session=cookie_value; Path=/; HttpOnly; SameSite=Lax'
            $parsedSessionCookie = $this->extractSessionCookieFromString($rawSessionCookie);

            if ($parsedSessionCookie) {
                $cookieName = Arr::get($parsedSessionCookie, 'name');
                $cookieValue = Arr::get($parsedSessionCookie, 'value');

                // Set the session cookie in the request
                $request->cookies->set(
                    $cookieName,
                    $cookieValue
                );
            }
        }

        return $request;
    }
}
