<?php

declare(strict_types=1);

namespace Tests\Routing;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use DemonDextralHorn\Routing\RouteDispatcher;
use DemonDextralHorn\Data\TargetRouteData;
use DemonDextralHorn\Enums\HttpHeaderType;
use DemonDextralHorn\Events\RouteDispatchFailedEvent;

#[CoversClass(RouteDispatcher::class)]
final class RouteDispatcherTest extends TestCase
{
    private RouteDispatcher $dispatcher;

    public function setUp(): void
    {
        parent::setUp();

        $this->dispatcher = app(RouteDispatcher::class);
    }

    #[Test]
    public function it_dispatches_a_get_request_with_route_params_and_query_params(): void
    {
        /* SETUP */
        Route::get('/sample/{id}/route', fn ($id) => response()->json([
            'id' => $id,
        ]))->name('sample.route');
        $id = 42;
        $filter = 'latest';
        $targetRouteData = new TargetRouteData(
            routeName: 'sample.route',
            method: Request::METHOD_GET,
            routeParams: ['id' => $id],
            queryParams: ['filter' => $filter],
            headers: [],
            cookies: []
        );

        /* EXECUTE */
        $response = $this->dispatcher->dispatch(
            $targetRouteData
        );

        /* ASSERT */
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(['id' => $id], $data);
    }

    #[Test]
    public function it_returns_404_for_invalid_route(): void
    {
        /* SETUP */
        $invalidRouteName = 'invalid.route';
        $this->expectException(RouteNotFoundException::class);
        $targetRouteData = new TargetRouteData(
            routeName: $invalidRouteName,
            method: Request::METHOD_GET,
            routeParams: [],
            queryParams: [],
            headers: [],
            cookies: []
        );

        /* EXECUTE */
        $this->dispatcher->dispatch(
            $targetRouteData
        );
    }

    #[Test]
    public function it_successfully_dispatches_route_with_headers(): void
    {
        /* SETUP */
        $message = 'Success';
        Route::get('/sample/route', fn () => response()->json([
            'message' => $message,
        ]))->name('sample.route');
        $headers = [HttpHeaderType::AUTHORIZATION->value => 'Bearer token'];
        $targetRouteData = new TargetRouteData(
            routeName: 'sample.route',
            method: Request::METHOD_GET,
            routeParams: [],
            queryParams: [],
            headers: $headers,
            cookies: []
        );

        /* EXECUTE */
        $response = $this->dispatcher->dispatch(
            $targetRouteData
        );

        /* ASSERT */
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(['message' => $message], $data);
    }

    #[Test]
    public function it_dispatches_a_get_request_with_array_query_params(): void
    {
        /* SETUP */
        $message = 'Success';
        Route::get('/sample/route', fn () => response()->json([
            'message' => $message,
        ]))->name('sample.route');
        $items = [1, 2, 3];
        $targetRouteData = new TargetRouteData(
            routeName: 'sample.route',
            method: Request::METHOD_GET,
            routeParams: [],
            queryParams: ['items' => $items],
            headers: [],
            cookies: []
        );

        /* EXECUTE */
        $response = $this->dispatcher->dispatch(
            $targetRouteData
        );

        /* ASSERT */
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(['message' => $message], $data);
    }

    #[Test]
    public function it_dispatches_request_with_session_cookie(): void
    {
        /* SETUP */
        $expectedSessionValue = 'sample_session_value';
        $cookieName = config('demon-dextral-horn.defaults.session_cookie_name');
    
        Route::get('/sample/route', function (Request $request) use ($cookieName) {
            return response()->json([
                // value via Laravel helper -> reads from Cookie header / cookies bag
                'session_cookie' => $request->cookie($cookieName),
                // cookie bag direct access (optional)
                'cookies_bag_value' => $request->cookies->get($cookieName),
            ]);
        })->name('sample.route');
        $rawCookie = $cookieName . '=' . $expectedSessionValue . '; Path=/; HttpOnly; SameSite=Lax';
        $targetRouteData = new TargetRouteData(
            routeName: 'sample.route',
            method: Request::METHOD_GET,
            routeParams: [],
            queryParams: [],
            headers: [],
            cookies: ['session_cookie' => $rawCookie]
        );

        /* EXECUTE */
        $response = $this->dispatcher->dispatch(
            $targetRouteData
        );
    
        /* ASSERT */
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\Response::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($expectedSessionValue, Arr::get($data, 'session_cookie'));
        $this->assertEquals($expectedSessionValue, Arr::get($data, 'cookies_bag_value'));
    }

    #[Test]
    public function it_dispatches_a_get_request_with_laravel_session_cookie()
    {
        /* SETUP */
        $message = 'Success';
        $sessionCookieName = config('demon-dextral-horn.defaults.session_cookie_name');
        $cookieValue = 'cookie_value';
        Route::get('/sample/route', fn () => response()->json([
            'message' => $message,
            'session_cookie' => $sessionCookieName . '=' . request()->cookie($sessionCookieName),
        ]))->name('sample.route');
        $cookies = ['session_cookie' => $sessionCookieName . '=' . $cookieValue];
        $targetRouteData = new TargetRouteData(
            routeName: 'sample.route',
            method: Request::METHOD_GET,
            routeParams: [],
            queryParams: [],
            headers: [],
            cookies: $cookies
        );

        /* EXECUTE */
        $response = $this->dispatcher->dispatch(
            $targetRouteData
        );

        /* ASSERT */
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($message, Arr::get($data, 'message'));
        $this->assertEquals($sessionCookieName . '=' . $cookieValue, Arr::get($data, 'session_cookie'));
    }

    #[Test]
    public function it_fires_event_on_dispatch_failure(): void
    {
        /* SETUP */
        $routeName = 'failing.route';
        Event::fake();
        $this->withoutExceptionHandling(); // This lets exception bubble up to RouteDispatcher
        Route::get('/failing/route', function () {
            throw new \RuntimeException('Sample exception for testing');
        })->name($routeName);
        $targetRouteData = new TargetRouteData(
            routeName: $routeName,
            method: Request::METHOD_GET,
            routeParams: [],
            queryParams: [],
            headers: [],
            cookies: []
        );

        /* EXECUTE */
        $response = $this->dispatcher->dispatch($targetRouteData);

        /* ASSERT */
        Event::assertDispatched(RouteDispatchFailedEvent::class, function ($event) use ($targetRouteData) {
            return $event->exception instanceof \RuntimeException
                && $event->targetRouteData === $targetRouteData;
        });
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertEquals('Internal Server Error', $response->getContent());
    }
}