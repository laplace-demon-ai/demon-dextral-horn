<?php

declare(strict_types=1);

namespace Tests\Traits;

use DemonDextralHorn\Data\CookiesData;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use DemonDextralHorn\Enums\HttpHeaderType;
use DemonDextralHorn\Enums\PrefetchType;
use DemonDextralHorn\Traits\RequestParsingTrait;
use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\HeadersData;

#[CoversClass(RequestParsingTrait::class)]
final class RequestParsingTraitTest extends TestCase
{
    private $anonymousClass;

    public function setUp(): void
    {
        parent::setUp();

        // Create an anonymous class that uses the RequestParsingTrait
        $this->anonymousClass = new class {
            use RequestParsingTrait;

            // Since extractSessionCookieFromString and other methods are protected, we create public methods to access them for testing
            public function callExtractSessionCookieFromString(?string $cookie)
            {
                return $this->extractSessionCookieFromString($cookie);
            }

            public function callNormalizeRouteParams(array $params): array
            {
                return $this->normalizeRouteParams($params);
            }

            public function callNormalizeQueryParams(array $params): array
            {
                return $this->normalizeQueryParams($params);
            }

            public function callPrepareCookies(?RequestData $requestData, array $overrides = []): array
            {
                return $this->prepareCookies($requestData, $overrides);
            }

            public function callPrepareMappedHeaders(?RequestData $requestData, array $overrides = []): array
            {
                return $this->prepareMappedHeaders($requestData, $overrides);
            }
        };
    }

    #[Test]
    public function it_succesfully_extracts_session_cookie_from_string(): void
    {
        /* SETUP */
        $sessionCookieString = config('demon-dextral-horn.defaults.session_cookie_name') . '=cookie_value; Path=/; HttpOnly; SameSite=Lax';

        /* EXECUTE */
        $sessionCookie = $this->anonymousClass->callExtractSessionCookieFromString($sessionCookieString);

        /* ASSERT */
        $this->assertIsArray($sessionCookie);
        $this->assertArrayHasKey('name', $sessionCookie);
        $this->assertArrayHasKey('value', $sessionCookie);
        $this->assertSame(config('demon-dextral-horn.defaults.session_cookie_name'), Arr::get($sessionCookie, 'name'));
        $this->assertSame('cookie_value', Arr::get($sessionCookie, 'value'));
    }

    #[Test]
    public function it_returns_null_if_cookie_not_found(): void
    {
        /* SETUP */
        $cookieString = 'another_cookie=abc123; Path=/; Secure';

        /* EXECUTE */
        $sessionCookie = $this->anonymousClass->callExtractSessionCookieFromString($cookieString);

        /* ASSERT */
        $this->assertNull($sessionCookie);
    }

    #[Test]
    public function it_handles_urlencoded_cookie_value(): void
    {
        /* SETUP */
        $cookieString = config('demon-dextral-horn.defaults.session_cookie_name') . '=my%20encoded%20value; Path=/; HttpOnly';

        /* EXECUTE */
        $sessionCookie = $this->anonymousClass->callExtractSessionCookieFromString($cookieString);

        /* ASSERT */
        $this->assertSame('my encoded value', Arr::get($sessionCookie, 'value'));
    }

    #[Test]
    public function it_ignores_flags_without_equals_sign_malformed_cookie_naming(): void
    {
        /* SETUP */
        $cookieString = config('demon-dextral-horn.defaults.session_cookie_name') . ':value123; HttpOnly; Secure; SameSite=Strict';

        /* EXECUTE */
        $sessionCookie = $this->anonymousClass->callExtractSessionCookieFromString($cookieString);

        /* ASSERT */
        $this->assertNull($sessionCookie);
    }

    #[Test]
    public function it_returns_null_on_empty_string(): void
    {
        /* EXECUTE */
        $sessionCookie = $this->anonymousClass->callExtractSessionCookieFromString('');

        /* ASSERT */
        $this->assertNull($sessionCookie);
    }

    #[Test]
    public function it_returns_null_when_cookie_string_is_null(): void
    {
        /* EXECUTE */
        $sessionCookie = $this->anonymousClass->callExtractSessionCookieFromString(null);

        /* ASSERT */
        $this->assertNull($sessionCookie);
    }

    #[Test]
    public function it_normalizes_route_params_by_sorting_keys(): void
    {
        /* SETUP */
        $params = ['role' => 3, 'id' => 123, 'permission' => 6];

        /* EXECUTE */
        $normalized = $this->anonymousClass->callNormalizeRouteParams($params);

        /* ASSERT */
        $this->assertSame(['id' => 123, 'permission' => 6, 'role' => 3], $normalized);
    }

    #[Test]
    public function it_returns_empty_array_when_normalizing_empty_route_params(): void
    {
        /* EXECUTE */
        $normalized = $this->anonymousClass->callNormalizeRouteParams([]);

        /* ASSERT */
        $this->assertSame([], $normalized);
    }

    #[Test]
    public function it_normalizes_query_params_by_sorting_keys_and_values(): void
    {
        /* SETUP */
        $params = [
            'page' => 2,
            'tags' => ['php', 'laravel'],
            'filter' => 'latest',
        ];

        /* EXECUTE */
        $normalized = $this->anonymousClass->callNormalizeQueryParams($params);

        /* ASSERT */
        $this->assertSame([
            'filter' => 'latest',
            'page' => 2,
            'tags' => ['laravel', 'php'], // sorted
        ], $normalized);
    }

    #[Test]
    public function it_converts_empty_strings_to_null_in_query_params(): void
    {
        /* SETUP */
        $params = [
            'filter' => '',
            'tags' => ['php', ''],
        ];

        /* EXECUTE */
        $normalized = $this->anonymousClass->callNormalizeQueryParams($params);

        /* ASSERT */
        $this->assertSame([
            'filter' => null,
            'tags' => [null, 'php'], // values sorted alphabetically, null stays first
        ], $normalized);
    }

    #[Test]
    public function it_normalizes_nested_query_params(): void
    {
        /* SETUP */
        $params = [
            'filters' => [
                'status' => ['active', 'archived'],
                'date' => ['2025-01-01', '2024-12-31'],
            ],
            'page' => 1,
        ];

        /* EXECUTE */
        $normalized = $this->anonymousClass->callNormalizeQueryParams($params);

        /* ASSERT */
        $this->assertSame([
            'filters' => [
                'date' => ['2024-12-31', '2025-01-01'],
                'status' => ['active', 'archived'],
            ],
            'page' => 1,
        ], $normalized);
    }

    #[Test]
    public function it_returns_empty_array_when_normalizing_empty_query_params(): void
    {
        /* EXECUTE */
        $normalized = $this->anonymousClass->callNormalizeQueryParams([]);

        /* ASSERT */
        $this->assertSame([], $normalized);
    }

    #[Test]
    public function it_prepares_cookies_with_session_cookie(): void
    {
        /* SETUP */
        $sessionValue = 'session_value';
        $cookiesData = new CookiesData(
            sessionCookie: $sessionValue
        );
        $requestData = new RequestData(
            uri: '/sample_route',
            method: Request::METHOD_GET,
            headers: new HeadersData(),
            payload: [],
            routeName: 'sample.route',
            cookies: $cookiesData
        );

        /* EXECUTE */
        $cookies = $this->anonymousClass->callPrepareCookies($requestData);

        /* ASSERT */
        $this->assertSame(['session_cookie' => $sessionValue], $cookies);
    }

    #[Test]
    public function it_applies_cookie_overrides(): void
    {
        /* SETUP */
        $sessionValue = 'session_value';
        $overrideSessionValue = 'override_value';
        $cookiesData = new CookiesData(
            sessionCookie: $sessionValue
        );
        $requestData = new RequestData(
            uri: '/sample_route',
            method: Request::METHOD_GET,
            headers: new HeadersData(),
            payload: [],
            routeName: 'sample.route',
            cookies: $cookiesData
        );
        $overrides = ['session_cookie' => $overrideSessionValue];

        /* EXECUTE */
        $cookies = $this->anonymousClass->callPrepareCookies($requestData, $overrides);

        /* ASSERT */
        $this->assertSame(['session_cookie' => $overrideSessionValue], $cookies);
    }

    #[Test]
    public function it_filters_out_null_cookie_values(): void
    {
        /* SETUP */
        $sessionValue = null;
        $cookiesData = new CookiesData(
            sessionCookie: $sessionValue
        );
        $requestData = new RequestData(
            uri: '/sample_route',
            method: Request::METHOD_GET,
            headers: new HeadersData(),
            payload: [],
            routeName: 'sample.route',
            cookies: $cookiesData
        );

        /* EXECUTE */
        $cookies = $this->anonymousClass->callPrepareCookies($requestData);

        /* ASSERT */
        $this->assertSame([], $cookies);
    }

    #[Test]
    public function it_prepares_mapped_headers_with_defaults_and_default_prefetch_header(): void
    {
        /* SETUP */
        $headersData = new HeadersData(
            authorization: 'Bearer token',
            accept: 'application/json',
            acceptLanguage: 'en-US'
        );
        $requestData = new RequestData(
            uri: '/sample_route',
            method: Request::METHOD_GET,
            headers: $headersData,
            routeName: 'sample.route',
        );

        /* EXECUTE */
        $headers = $this->anonymousClass->callPrepareMappedHeaders($requestData);

        /* ASSERT */
        $this->assertSame(
            [
                HttpHeaderType::AUTHORIZATION->value => 'Bearer token',
                HttpHeaderType::ACCEPT->value => 'application/json',
                HttpHeaderType::ACCEPT_LANGUAGE->value => 'en-US',
                config('demon-dextral-horn.defaults.prefetch_header') => PrefetchType::AUTO->value,
            ],
            $headers
        );
    }

    #[Test]
    public function it_applies_header_overrides(): void
    {
        /* SETUP */
        $headersData = new HeadersData(
            authorization: 'Bearer token',
            accept: 'application/json',
            acceptLanguage: 'en-US'
        );
        $requestData = new RequestData(
            uri: '/sample_route',
            method: Request::METHOD_GET,
            headers: $headersData,
            routeName: 'sample.route',
        );
        $acceptValue = 'text/html';
        $overrides = [
            HttpHeaderType::ACCEPT->value => $acceptValue,
        ];

        /* EXECUTE */
        $headers = $this->anonymousClass->callPrepareMappedHeaders($requestData, $overrides);

        /* ASSERT */
        $this->assertArrayHasKey(HttpHeaderType::ACCEPT->value, $headers);
        $this->assertSame($acceptValue, Arr::get($headers, HttpHeaderType::ACCEPT->value));
    }

    #[Test]
    public function it_filters_out_null_header_values(): void
    {
        /* SETUP */
        $headersData = new HeadersData(
            authorization: null,
            accept: null,
            acceptLanguage: null
        );
        $requestData = new RequestData(
            uri: '/sample_route',
            method: Request::METHOD_GET,
            headers: $headersData,
            routeName: 'sample.route',
        );

        /* EXECUTE */
        $headers = $this->anonymousClass->callPrepareMappedHeaders($requestData);

        /* ASSERT */
        $this->assertSame(
            [
                config('demon-dextral-horn.defaults.prefetch_header') => PrefetchType::AUTO->value,
            ],
            $headers
        );
    }
}
