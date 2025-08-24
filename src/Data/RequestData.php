<?php

declare(strict_types=1);

namespace DemonDextralHorn\Data;

use Illuminate\Http\Request;
use Spatie\LaravelData\Data;

/**
 * DTO representing HTTP request data.
 *
 * @class RequestData
 */
final class RequestData extends Data
{
    /**
     * Create a new RequestData object.
     *
     * @param string $uri The request URI.
     * @param string $method The HTTP method.
     * @param HeadersData $headers The request headers.
     * @param array|null $payload The request payload (body).
     * @param CookiesData|null $cookies The request cookies.
     * @param string|null $routeName The name of the matched route.
     * @param array|null $routeParams The parameters of the matched route.
     * @param string|array|null $queryParams The query parameters.
     */
    public function __construct(
        public string $uri,
        public string $method,
        public HeadersData $headers,
        public ?array $payload = null,
        public ?CookiesData $cookies = null,
        public ?string $routeName = null,
        public ?array $routeParams = null,
        public string|array|null $queryParams = null,
    ) {}

    /**
     * Create a new instance from a Request object.
     *
     * @param Request $request
     *
     * @return static
     */
    public static function fromRequest(Request $request): static
    {
        return new self(
            uri: $request->getRequestUri(),
            method: $request->method(),
            headers: HeadersData::fromHeaders($request->headers->all()),
            payload: $request->getPayload()->all(),
            cookies: CookiesData::fromCookies($request->cookies->all()),
            routeName: $request->route()?->getName(),
            routeParams: $request->route()?->parameters(),
            queryParams: $request->query(),
        );
    }
}
