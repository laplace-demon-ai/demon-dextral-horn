<?php

declare(strict_types=1);

namespace Tests\Handlers;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Http\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use DemonDextralHorn\Handlers\TargetRouteHandler;
use DemonDextralHorn\Resolvers\RouteParamResolver;
use DemonDextralHorn\Resolvers\QueryParamResolver;
use DemonDextralHorn\Resolvers\HeadersResolver;
use DemonDextralHorn\Routing\RouteDispatcher;
use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Resolvers\CookiesResolver;

/**
 * Test for TargetRouteHandler.
 * 
 * @class TargetRouteHandlerTest
 */
final class TargetRouteHandlerTest extends TestCase
{
    private RouteParamResolver $routeParamResolver;
    private QueryParamResolver $queryParamResolver;
    private HeadersResolver $headersResolver;
    private CookiesResolver $cookiesResolver;
    private RouteDispatcher $routeDispatcher;
    private TargetRouteHandler $handler;
    private RequestData $requestData;
    private ResponseData $responseData;

    public function setUp(): void
    {
        parent::setUp();

        $this->routeDispatcher = app(RouteDispatcher::class);
        $this->routeParamResolver = app(RouteParamResolver::class);
        $this->queryParamResolver = app(QueryParamResolver::class);
        $this->headersResolver = app(HeadersResolver::class);
        $this->cookiesResolver = app(CookiesResolver::class);
        $this->handler = new TargetRouteHandler(
            $this->routeParamResolver,
            $this->queryParamResolver,
            $this->headersResolver,
            $this->cookiesResolver,
            $this->routeDispatcher
        );
        $request = Request::create(
            uri: "/sample_trigger_route",
            method: Request::METHOD_GET,
        );
        $this->requestData = RequestData::fromRequest($request);
        $response = new Response('trigger_response', 200);
        $this->responseData = ResponseData::fromResponse($response);
    }

    #[Test]
    public function it_returns_404_for_invalid_route()
    {
        /* SETUP */
        $routes = [
            [
                'route' => 'invalid.route',
                'method' => Request::METHOD_GET,
            ]
        ];
        $this->expectException(RouteNotFoundException::class);

        /* EXECUTE */
        $this->handler->handle($routes, $this->requestData, $this->responseData);
    }

    // todo more test will be added once the response caching is implemented
}