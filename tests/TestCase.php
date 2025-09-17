<?php

declare(strict_types=1);

namespace Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use DemonDextralHorn\DemonDextralHornServiceProvider;
use DemonDextralHorn\Middleware\PrefetchTriggerMiddleware;
use DemonDextralHorn\Middleware\PrefetchCacheMiddleware;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Spatie\ResponseCache\ResponseCacheServiceProvider;

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
            ResponseCacheServiceProvider::class,
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
        // Define a success route which uses PrefetchTriggerMiddleware middleware
        $router->middleware(PrefetchTriggerMiddleware::class)
            ->get('/prefetch-route-success', function () {
                return new Response('ok', Response::HTTP_OK);
            });

        // Define an error route which uses PrefetchTriggerMiddleware middleware
        $router->middleware(PrefetchTriggerMiddleware::class)
            ->get('/prefetch-route-error', function () {
                return new Response('error', Response::HTTP_INTERNAL_SERVER_ERROR);
            });

        // Define a success post route which uses PrefetchTriggerMiddleware middleware
        $router->middleware(PrefetchTriggerMiddleware::class)
            ->post('/post/prefetch-route-success', function () {
                return new Response('ok', Response::HTTP_OK);
            });

        // Define a success login route which uses PrefetchTriggerMiddleware middleware, add laravel_session cookie to response header set-cookie
        $router->middleware(PrefetchTriggerMiddleware::class)
            ->post('/post/prefetch-login-success', function () {
                $response = new Response('ok', Response::HTTP_OK);
                $response->headers->setCookie(
                    new Cookie(config('demon-dextral-horn.defaults.session_cookie_name'), 'session_value')
                );

                return $response;
            });

        // Define route with route name sample.target.route.name which has multiple route params
        $router->get('/{param1}/{param2}/sample-target-route-success/{param3}/{param4}', function () {
                return new Response('Sample Target Route', Response::HTTP_OK);
            })->name('sample.target.route.name');

        // Define route with no params, content-type text/event-stream to make it non-cacheable by default validator
        $router->get('/sample-target-route-no-params', function () {
                return new Response('Sample Target Route No Params', Response::HTTP_OK, [
                    'Content-Type' => 'text/event-stream',
                ]);
            })->name('sample.target.route.no.params');

        // Define a normal route with no params, no content-type etc.
        $router->get('/sample-target-route-default', function () {
                return new Response('Sample Target Route', Response::HTTP_OK);
            })->name('sample.target.route.default');

        // Define target route with middleware PrefetchCacheMiddleware
        $router->middleware(PrefetchCacheMiddleware::class)
            ->get('/sample-target-route-with-middleware', function () {
                return new Response('ok', Response::HTTP_OK);
            })->name('sample.target.route.with.middleware');

        // Define route with auth middleware
        $router->middleware('auth')->get('/auth-protected-route', function () {
            return new Response('ok', Response::HTTP_OK);
        })->name('auth.protected.route');

        // Define public route with no auth middleware
        $router->get('/public-route-no-auth', function () {
            return new Response('ok', Response::HTTP_OK);
        })->name('public.route.no.auth');

        // Define route with auth middleware that requires token from login request
        $router->middleware('auth')->get('/sample-target-route-requires-token-from-login-request', function () {
            return new Response('ok', Response::HTTP_OK);
        })->name('sample.target.route.requires.token.from.login.request');
    }
}