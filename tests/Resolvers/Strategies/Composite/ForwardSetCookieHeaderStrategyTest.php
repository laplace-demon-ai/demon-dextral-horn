<?php

declare(strict_types=1);

namespace Tests\Resolvers\Strategies\Composite;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Exceptions\MissingStrategyOptionException;
use DemonDextralHorn\Resolvers\Strategies\Composite\ForwardSetCookieHeaderStrategy;

#[CoversClass(ForwardSetCookieHeaderStrategy::class)]
final class ForwardSetCookieHeaderStrategyTest extends TestCase
{
    private ForwardSetCookieHeaderStrategy $forwardSetCookieHeaderStrategy;

    public function setUp(): void
    {
        parent::setUp();

        $this->forwardSetCookieHeaderStrategy = app(ForwardSetCookieHeaderStrategy::class);
    }

    #[Test]
    public function it_tests_forward_set_cookie_header_strategy_when_set_cookie_is_present_in_response_headers(): void
    {
        /* SETUP */
        $cookieName = config('demon-dextral-horn.defaults.session_cookie_name');
        $sessionValue = 'session_value';
        $firstCookie = new Cookie($cookieName, $sessionValue, 0, '/', null, false, true);
        $request = Request::create(
            uri: "/sample_endpoint",
            method: Request::METHOD_GET,
        );
        $requestData = RequestData::fromRequest($request);
        $response = new Response(json_encode(['data' => 'ok']), Response::HTTP_OK);
        $response->headers->setCookie($firstCookie);
        $responseData = ResponseData::fromResponse($response);

        /* EXECUTE */
        $result = $this->forwardSetCookieHeaderStrategy->handle(
            requestData: $requestData,
            responseData: $responseData,
            options: [
                'source_key' => config('demon-dextral-horn.defaults.session_cookie_name'),
            ]
        );

        /* ASSERT */
        $this->assertStringContainsString($cookieName, $result);
        $this->assertStringContainsString($sessionValue, $result);
    }

    #[Test]
    public function it_throws_missing_strategy_option_exception_when_source_key_missing_in_options(): void
    {
        /* SETUP */
        $request = Request::create(
            uri: "/sample_endpoint",
            method: Request::METHOD_GET,
        );
        $requestData = RequestData::fromRequest($request);
        $response = new Response(json_encode(['data' => 'ok']), Response::HTTP_OK);
        $responseData = ResponseData::fromResponse($response);
        $this->expectException(MissingStrategyOptionException::class);

        /* EXECUTE */
        $this->forwardSetCookieHeaderStrategy->handle(
            requestData: $requestData,
            responseData: $responseData,
            options: []
        );
    }

    #[Test]
    public function it_returns_null_when_no_cookies_are_present_in_response_headers(): void
    {
        /* SETUP */
        $request = Request::create(
            uri: "/sample_endpoint",
            method: Request::METHOD_GET,
        );
        $requestData = RequestData::fromRequest($request);
        $response = new Response(json_encode(['data' => 'ok']), Response::HTTP_OK);
        $responseData = ResponseData::fromResponse($response);

        /* EXECUTE */
        $result = $this->forwardSetCookieHeaderStrategy->handle(
            requestData: $requestData,
            responseData: $responseData,
            options: [
                'source_key' => config('demon-dextral-horn.defaults.session_cookie_name'),
            ]
        );

        /* ASSERT */
        $this->assertNull($result);
    }

    #[Test]
    public function it_returns_null_when_cookies_exist_but_no_matching_key(): void
    {
        /* SETUP */
        $cookieName = 'other_cookie';
        $cookie = new Cookie($cookieName, 'other_value', 0, '/', null, false, true);
        $request = Request::create("/sample_endpoint", Request::METHOD_GET);
        $requestData = RequestData::fromRequest($request);
        $response = new Response('ok', Response::HTTP_OK);
        $response->headers->setCookie($cookie);
        $responseData = ResponseData::fromResponse($response);

        /* EXECUTE */
        $result = $this->forwardSetCookieHeaderStrategy->handle(
            requestData: $requestData,
            responseData: $responseData,
            options: [
                'source_key' => config('demon-dextral-horn.defaults.session_cookie_name'),
            ]
        );

        /* ASSERT */
        $this->assertNull($result);
    }

    #[Test]
    public function it_returns_correct_cookie_when_multiple_cookies_present_and_one_matches(): void
    {
        /* SETUP */
        $cookieName = config('demon-dextral-horn.defaults.session_cookie_name');
        $sessionValue = 'session_value';
        $matchingCookie = new Cookie($cookieName, $sessionValue, 0, '/', null, false, true);
        $otherCookie = new Cookie('other_cookie', 'other_value', 0, '/', null, false, true);
        $request = Request::create("/sample_endpoint", Request::METHOD_GET);
        $requestData = RequestData::fromRequest($request);
        $response = new Response('ok', Response::HTTP_OK);
        $response->headers->setCookie($otherCookie);
        $response->headers->setCookie($matchingCookie);
        $responseData = ResponseData::fromResponse($response);

        /* EXECUTE */
        $result = $this->forwardSetCookieHeaderStrategy->handle(
            requestData: $requestData,
            responseData: $responseData,
            options: [
                'source_key' => $cookieName,
            ]
        );

        /* ASSERT */
        $this->assertStringContainsString($cookieName, $result);
        $this->assertStringContainsString($sessionValue, $result);
    }

    #[Test]
    public function it_returns_null_when_response_data_is_null(): void
    {
        /* EXECUTE */
        $result = $this->forwardSetCookieHeaderStrategy->handle(
            requestData: null,
            responseData: null,
            options: [
                'source_key' => config('demon-dextral-horn.defaults.session_cookie_name'),
            ]
        );

        /* ASSERT */
        $this->assertNull($result);
    }
}