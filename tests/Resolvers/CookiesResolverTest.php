<?php

declare(strict_types=1);

namespace Tests\Resolvers;

use DemonDextralHorn\Resolvers\CookiesResolver;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Resolvers\Strategies\Source\ResponseSessionHeaderStrategy;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Arr;

/**
 * Test for CookiesResolver.
 * 
 * @class CookiesResolverTest
 */
final class CookiesResolverTest extends TestCase
{
    private CookiesResolver $resolver;

    public function setUp(): void
    {
        parent::setUp();

        $this->resolver = app(CookiesResolver::class);
    }

    #[Test]
    public function it_tests_if_set_cookie_header_of_response_is_forwarded(): void
    {
        /* SETUP */
        $cookieName = config('demon-dextral-horn.defaults.session_cookie_name');
        $sessionValue = 'session_value';
        $routeDefinition = [
            'method' => Request::METHOD_GET,
            'route' => 'sample.target.route.requires.session_cookie',
            'cookies' => [
                'session_cookie' => [
                    'strategy' => ResponseSessionHeaderStrategy::class,
                    'options' => [
                        'key' => $cookieName,
                    ],
                ],
            ],
        ]; // Route definition for the session cookie
        $response = new Response(json_encode(['data' => 'ok']), Response::HTTP_OK);
        $firstCookie = new Cookie($cookieName, $sessionValue, 0, '/', null, false, true);
        $secondCookie = new Cookie('another_cookie', 'another_value', 0, '/', null, false, true);
        $response->headers->setCookie($firstCookie);
        $response->headers->setCookie($secondCookie);
        $responseData = ResponseData::fromResponse($response);

        /* EXECUTE */
        $resolvedParams = $this->resolver->resolve(
            targetRouteDefinition: $routeDefinition, 
            responseData: $responseData
        );

        /* ASSERT */
        $this->assertIsArray($resolvedParams);
        $this->assertArrayHasKey('session_cookie', $resolvedParams);
        $this->assertStringContainsString($cookieName, Arr::get($resolvedParams, 'session_cookie'));
        $this->assertStringContainsString($sessionValue, Arr::get($resolvedParams, 'session_cookie'));
    }

    #[Test]
    public function it_forwards_session_cookie_when_set_cookie_is_not_provided(): void
    {
        /* SETUP */
        $cookieName = config('demon-dextral-horn.defaults.session_cookie_name');
        $sessionValue = 'session_value';
        $routeDefinition = [
            'method' => Request::METHOD_GET,
            'route' => 'sample.target.route.without.set.cookie',
        ];
        $response = new Response(json_encode(['data' => 'ok']), Response::HTTP_OK);
        $responseData = ResponseData::fromResponse($response);
        $request = Request::create(
            uri: "/sample_trigger_route",
            method: Request::METHOD_GET,
        );
        $request->cookies->set(
            $cookieName,
            $sessionValue
        );
        $requestData = RequestData::fromRequest($request);

        /* EXECUTE */
        $resolvedParams = $this->resolver->resolve(
            targetRouteDefinition: $routeDefinition,
            requestData: $requestData,
            responseData: $responseData
        );

        /* ASSERT */
        $this->assertIsArray($resolvedParams);
        $this->assertArrayHasKey('session_cookie', $resolvedParams);
        $this->assertStringContainsString($sessionValue, Arr::get($resolvedParams, 'session_cookie'));
    }
}