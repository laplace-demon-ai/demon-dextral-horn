<?php

declare(strict_types=1);

namespace DemonDextralHorn\Data;

use Illuminate\Http\Request;
use Spatie\LaravelData\Data;
use Illuminate\Support\Arr;

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
     * @param array|null $queryParams The query parameters.
     */
    public function __construct(
        public string $uri,
        public string $method,
        public HeadersData $headers,
        public ?array $payload = null,
        public ?CookiesData $cookies = null,
        public ?string $routeName = null,
        public ?array $routeParams = null,
        public ?array $queryParams = null,
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

    /**
     * Serialize the current object to an array.
     *
     * @return array
     */
    public function __serialize(): array
    {
        return [
            'uri' => $this->uri,
            'method' => $this->method,
            'headers' => $this->headers->__serialize(),
            'payload' => $this->payload,
            'cookies' => $this->cookies?->__serialize(),
            'routeName' => $this->routeName,
            'routeParams' => $this->routeParams,
            'queryParams' => $this->queryParams,
        ];
    }

    /**
     * Unserialize the data into the current object.
     *
     * @param array $data
     *
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->uri = $data['uri'];
        $this->method = $data['method'];

        // Unserialize HeadersData
        $headersArray = Arr::get($data, 'headers', []);
        $this->headers = new HeadersData(
            authorization: Arr::get($headersArray, 'authorization'),
            accept: Arr::get($headersArray, 'accept'),
            acceptLanguage: Arr::get($headersArray, 'acceptLanguage'),
            prefetchHeader: Arr::get($headersArray, 'prefetchHeader'),
            setCookie: Arr::get($headersArray, 'setCookie'),
        );

        $this->payload = Arr::get($data, 'payload');

        // Unserialize CookiesData
        $cookiesArray = Arr::get($data, 'cookies');
        $this->cookies = $cookiesArray
            ? new CookiesData(
                sessionCookie: Arr::get($cookiesArray, 'sessionCookie'),
            )
            : null;

        $this->routeName = Arr::get($data, 'routeName');
        $this->routeParams = Arr::get($data, 'routeParams');
        $this->queryParams = Arr::get($data, 'queryParams');
    }
}
