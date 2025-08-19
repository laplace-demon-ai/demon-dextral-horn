<?php

declare(strict_types=1);

namespace Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use DemonDextralHorn\DemonDextralHornServiceProvider;
use DemonDextralHorn\Middleware\DemonDextralHornPrefetchMiddleware;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Base test case for the package.
 *
 * @class TestCase
 */
class TestCase extends OrchestraTestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @param $app
     *
     * @return string[]
     */
    protected function getPackageProviders($app): array
    {
        return [
            DemonDextralHornServiceProvider::class,
        ];
    }

    /**
     * Define the routes for the test.
     *
     * @param $router
     *
     * @return void
     */
    protected function defineRoutes($router): void
    {
        // Define a success route which uses DemonDextralHornPrefetchMiddleware middleware
        $router->middleware(DemonDextralHornPrefetchMiddleware::class)
            ->get('/prefetch-route-success', function () {
                return new Response('ok', 200);
            });

        // Define an error route which uses DemonDextralHornPrefetchMiddleware middleware
        $router->middleware(DemonDextralHornPrefetchMiddleware::class)
            ->get('/prefetch-route-error', function () {
                return new Response('error', 500);
            });

        // Define a success post route which uses DemonDextralHornPrefetchMiddleware middleware
        $router->middleware(DemonDextralHornPrefetchMiddleware::class)
            ->post('/post/prefetch-route-success', function () {
                return new Response('ok', 200);
            });

        // Define a success login route which uses DemonDextralHornPrefetchMiddleware middleware, add laravel_session cookie to response header set-cookie
        $router->middleware(DemonDextralHornPrefetchMiddleware::class)
            ->post('/post/prefetch-login-success', function () {
                $response = new Response('ok', 200);
                $response->headers->setCookie(
                    new Cookie('laravel_session', 'session_value')
                );

                return $response;
            });

        // todo we will have sample routes with middleware and without middleware where it will also be a testing for what happens if target route itself is also a trigger route,, not only middleware enough for ths, also need to add the target route as trigger in the config 
        // Create route with route name sample.target.route.name
        $router->get('/{param1}/{param2}/sample-target-route-success/{param3}/{param4}', function () {
                return new Response('Sample Target Route', 200);
            })->name('sample.target.route.name');
    }
}