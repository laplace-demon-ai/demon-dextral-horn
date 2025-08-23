<?php

declare(strict_types=1);

namespace Tests\Resolvers;

use DemonDextralHorn\Resolvers\HeadersResolver;
use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Enums\PrefetchType;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * Test for HeadersResolver.
 * 
 * @class HeadersResolverTest
 */
final class HeadersResolverTest extends TestCase
{
    private HeadersResolver $resolver;
    private ?RequestData $requestData = null;
    private string $prefetchHeaderName;
    private string $prefetchHeaderValue;

    public function setUp(): void
    {
        parent::setUp();

        $this->resolver = app(HeadersResolver::class);
        $this->prefetchHeaderName = config('demon-dextral-horn.defaults.prefetch_header'); // Demon-Prefetch-Call
        $this->prefetchHeaderValue = PrefetchType::AUTO->value;
    }

    #[Test]
    public function it_tests_if_custom_prefetch_header_is_successfully_added(): void
    {
        /* SETUP */
        $request = Request::create(
            uri: "/sample_trigger_route",
            method: Request::METHOD_GET,
        );
        $this->requestData = RequestData::fromRequest($request);

        /* EXECUTE */
        $resolvedParams = $this->resolver->resolve(requestData: $this->requestData);

        /* ASSERT */
        $this->assertIsArray($resolvedParams);
        $this->assertSame($this->prefetchHeaderValue, Arr::get($resolvedParams, $this->prefetchHeaderName));
    }

    #[Test]
    public function it_tests_if_authentication_headers_are_forwarded(): void
    {
        /* SETUP */
        $bearerToken = 'your-token-here';
        $request = Request::create(
            uri: "/sample_trigger_route",
            method: Request::METHOD_GET,
        );
        $request->headers->set('Authorization', 'Bearer ' . $bearerToken);
        $this->requestData = RequestData::fromRequest($request);

        /* EXECUTE */
        $resolvedParams = $this->resolver->resolve(requestData: $this->requestData);

        /* ASSERT */
        $this->assertIsArray($resolvedParams);
        $this->assertSame($this->prefetchHeaderValue, Arr::get($resolvedParams, $this->prefetchHeaderName));
        $this->assertSame('Bearer '. $bearerToken, Arr::get($resolvedParams, 'Authorization'));
    }

    #[Test]
    public function it_tests_if_accept_token_is_forwarded(): void
    {
        /* SETUP */
        $acceptToken = 'application/json';
        $request = Request::create(
            uri: "/sample_trigger_route",
            method: Request::METHOD_GET,
        );
        $request->headers->set('Accept', $acceptToken);
        $this->requestData = RequestData::fromRequest($request);

        /* EXECUTE */
        $resolvedParams = $this->resolver->resolve(requestData: $this->requestData);

        /* ASSERT */
        $this->assertIsArray($resolvedParams);
        $this->assertSame($this->prefetchHeaderValue, Arr::get($resolvedParams, $this->prefetchHeaderName));
        $this->assertSame($acceptToken, Arr::get($resolvedParams, 'Accept'));
    }

    #[Test]
    public function it_tests_if_accept_language_header_is_forwarded(): void
    {
        /* SETUP */
        $acceptLanguage = 'en-US';
        $request = Request::create(
            uri: "/sample_trigger_route",
            method: Request::METHOD_GET,
        );
        $request->headers->set('Accept-Language', $acceptLanguage);
        $this->requestData = RequestData::fromRequest($request);

        /* EXECUTE */
        $resolvedParams = $this->resolver->resolve(requestData: $this->requestData);

        /* ASSERT */
        $this->assertIsArray($resolvedParams);
        $this->assertSame($this->prefetchHeaderValue, Arr::get($resolvedParams, $this->prefetchHeaderName));
        $this->assertSame($acceptLanguage, Arr::get($resolvedParams, 'Accept-Language'));
    }
}