<?php

declare(strict_types=1);

namespace Tests\Handlers;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use DemonDextralHorn\Cache\ResponseCacheValidator;
use DemonDextralHorn\Events\NonCacheableResponseEvent;
use DemonDextralHorn\Handlers\TargetRouteHandler;
use DemonDextralHorn\Resolvers\RouteParamResolver;
use DemonDextralHorn\Resolvers\QueryParamResolver;
use DemonDextralHorn\Resolvers\HeadersResolver;
use DemonDextralHorn\Routing\RouteDispatcher;
use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Resolvers\CookiesResolver;

#[CoversClass(TargetRouteHandler::class)]
final class TargetRouteHandlerTest extends TestCase
{
    private RouteParamResolver $routeParamResolver;
    private QueryParamResolver $queryParamResolver;
    private HeadersResolver $headersResolver;
    private CookiesResolver $cookiesResolver;
    private RouteDispatcher $routeDispatcher;
    private ResponseCacheValidator $validator;
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
        $this->validator = app(ResponseCacheValidator::class);
        $this->handler = new TargetRouteHandler(
            $this->routeParamResolver,
            $this->queryParamResolver,
            $this->headersResolver,
            $this->cookiesResolver,
            $this->routeDispatcher,
            $this->validator,
        );
        $request = Request::create(
            uri: "/sample_trigger_route",
            method: Request::METHOD_GET,
        );
        $this->requestData = RequestData::fromRequest($request);
        $response = new Response('trigger_response', Response::HTTP_OK);
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

    #[Test]
    public function it_fires_event_for_non_cacheable_response()
    {
        /* SETUP */
        Event::fake();
        $routes = [
            [
                'route' => 'sample.target.route.no.params',
                'method' => Request::METHOD_GET,
            ]
        ];

        /* EXECUTE */
        $this->handler->handle($routes, $this->requestData, $this->responseData);

        /* ASSERT */
        Event::assertDispatched(NonCacheableResponseEvent::class);
    }
}