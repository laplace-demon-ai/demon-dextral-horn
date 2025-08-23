<?php

declare(strict_types=1);

namespace Tests\Routing;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use DemonDextralHorn\Routing\RouteDispatcher;

/**
 * Test for RouteDispatcher.
 * 
 * @class RouteDispatcherTest
 */
final class RouteDispatcherTest extends TestCase
{
    private RouteDispatcher $dispatcher;

    public function setUp(): void
    {
        parent::setUp();

        $this->dispatcher = app(RouteDispatcher::class);
    }

    #[Test]
    public function it_dispatches_a_get_request_with_route_params_and_query_params()
    {
        /* SETUP */
        Route::get('/sample/{id}/route', fn ($id) => response()->json([
            'id' => $id,
        ]))->name('sample.route');
        $id = 42;
        $filter = 'latest';

        /* EXECUTE */
        $response = $this->dispatcher->dispatch(
            routeName: 'sample.route',
            method: Request::METHOD_GET,
            routeParams: ['id' => $id],
            queryParams: ['filter' => $filter],
            headers: []
        );

        /* ASSERT */
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(['id' => $id], $data);
    }

    #[Test]
    public function it_returns_404_for_invalid_route()
    {
        /* SETUP */
        $invalidRouteName = 'invalid.route';
        $this->expectException(RouteNotFoundException::class);

        /* EXECUTE */
        $this->dispatcher->dispatch(
            routeName: $invalidRouteName,
            method: Request::METHOD_GET,
            routeParams: [],
            queryParams: [],
            headers: []
        );
    }

    #[Test]
    public function it_successfully_dispatches_route_with_headers()
    {
        /* SETUP */
        $message = 'Success';
        Route::get('/sample/route', fn () => response()->json([
            'message' => $message,
        ]))->name('sample.route');
        $headers = ['Authorization' => 'Bearer token'];

        /* EXECUTE */
        $response = $this->dispatcher->dispatch(
            routeName: 'sample.route',
            method: Request::METHOD_GET,
            routeParams: [],
            queryParams: [],
            headers: $headers
        );

        /* ASSERT */
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(['message' => $message], $data);
    }

    #[Test]
    public function it_dispatches_a_get_request_with_array_query_params()
    {
        /* SETUP */
        $message = 'Success';
        Route::get('/sample/route', fn () => response()->json([
            'message' => $message,
        ]))->name('sample.route');
        $items = [1, 2, 3];

        /* EXECUTE */
        $response = $this->dispatcher->dispatch(
            routeName: 'sample.route',
            method: Request::METHOD_GET,
            routeParams: [],
            queryParams: ['items' => $items],
            headers: []
        );

        /* ASSERT */
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(['message' => $message], $data);
    }
}