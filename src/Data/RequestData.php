<?php

declare(strict_types=1);

namespace DemonDextralHorn\Data;

use Spatie\LaravelData\Data;
use Illuminate\Http\Request;

/**
 * DTO representing HTTP request data.
 * 
 * @class RequestData
 */
final class RequestData extends Data
{
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
        return new static(
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