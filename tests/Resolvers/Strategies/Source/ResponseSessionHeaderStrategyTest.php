<?php

declare(strict_types=1);

namespace Tests\Resolvers\Strategies\Transform;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use DemonDextralHorn\Resolvers\Strategies\Source\ResponseSessionHeaderStrategy;
use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Exceptions\MissingStrategyOptionException;

/**
 * Test for ResponseSessionHeaderStrategy.
 * 
 * @class ResponseSessionHeaderStrategyTest
 */
final class ResponseSessionHeaderStrategyTest extends TestCase
{
    private ResponseSessionHeaderStrategy $responseSessionHeaderStrategy;

    public function setUp(): void
    {
        parent::setUp();

        $this->responseSessionHeaderStrategy = app(ResponseSessionHeaderStrategy::class);
    }

    #[Test]
    public function it_tests_response_session_header_strategy_when_set_cookie_is_present_in_response_headers(): void
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
        $response = new Response(json_encode(['data' => 'ok']), 200);
        $response->headers->setCookie($firstCookie);
        $responseData = ResponseData::fromResponse($response);

        /* EXECUTE */
        $result = $this->responseSessionHeaderStrategy->handle(
            requestData: $requestData,
            responseData: $responseData,
            options: [
                'key' => config('demon-dextral-horn.defaults.session_cookie_name'),
            ]
        );

        /* ASSERT */
        $this->assertStringContainsString($cookieName, $result);
        $this->assertStringContainsString($sessionValue, $result);
    }

    #[Test]
    public function it_throws_missing_strategy_option_exception_when_route_key_missing_in_options(): void
    {
        /* SETUP */
        $request = Request::create(
            uri: "/sample_endpoint",
            method: Request::METHOD_GET,
        );
        $requestData = RequestData::fromRequest($request);
        $responseData = new ResponseData(200);
        $this->expectException(MissingStrategyOptionException::class);

        /* EXECUTE */
        $this->responseSessionHeaderStrategy->handle(
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
        $response = new Response(json_encode(['data' => 'ok']), 200);
        $responseData = ResponseData::fromResponse($response);

        /* EXECUTE */
        $result = $this->responseSessionHeaderStrategy->handle(
            requestData: $requestData,
            responseData: $responseData,
            options: [
                'key' => config('demon-dextral-horn.defaults.session_cookie_name'),
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
        $response = new Response('ok', 200);
        $response->headers->setCookie($cookie);
        $responseData = ResponseData::fromResponse($response);

        /* EXECUTE */
        $result = $this->responseSessionHeaderStrategy->handle(
            requestData: $requestData,
            responseData: $responseData,
            options: [
                'key' => config('demon-dextral-horn.defaults.session_cookie_name'),
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
        $response = new Response('ok', 200);
        $response->headers->setCookie($otherCookie);
        $response->headers->setCookie($matchingCookie);
        $responseData = ResponseData::fromResponse($response);

        /* EXECUTE */
        $result = $this->responseSessionHeaderStrategy->handle(
            requestData: $requestData,
            responseData: $responseData,
            options: [
                'key' => $cookieName,
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
        $result = $this->responseSessionHeaderStrategy->handle(
            requestData: null,
            responseData: null,
            options: [
                'key' => config('demon-dextral-horn.defaults.session_cookie_name'),
            ]
        );

        /* ASSERT */
        $this->assertNull($result);
    }
}