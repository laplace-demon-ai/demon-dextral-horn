<?php

declare(strict_types=1);

namespace DemonDextralHorn\Routing;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

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
     * @param array $cookies
     *
     * @return Response
     */
    public function dispatch(
        string $routeName,
        string $method,
        array $routeParams,
        array $queryParams,
        array $headers,
        array $cookies
    ): Response {
        $url = $this->prepareUrl($routeName, $routeParams, $queryParams);

        $request = Request::create(
            uri: $url,
            method: $method,
        );

        $request = $this->prepareRequest($request, $headers, $cookies);

        $originalRequest = app('request');

        try {
            // Bind the request to the application, when request() is called it will return this request instance with proper headers, cookies, etc.
            app()->instance('request', $request);

            // Dispatch the request with the router
            return app('router')->dispatch($request);
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
        if (Arr::has($headers, 'Authorization')) {
            $request->server->set('HTTP_AUTHORIZATION', Arr::get($headers, 'Authorization'));
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

    /**
     * Extract the session cookie from the raw cookie string.
     * e.g. 'laravel_session=cookie_value; Path=/; HttpOnly; SameSite=Lax', it will return ['name' => 'laravel_session', 'value' => 'cookie_value']
     *
     * @param string $rawCookieString
     *
     * @return array|null
     */
    private function extractSessionCookieFromString(string $rawCookieString): ?array
    {
        $cookieName = config('demon-dextral-horn.defaults.session_cookie_name');

        // Cookie header may contain many parts separated by ';'
        foreach (explode(';', $rawCookieString) as $part) {
            $part = trim($part);

            if ($part === '') {
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
}
