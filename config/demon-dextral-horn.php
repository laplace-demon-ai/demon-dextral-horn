<?php

declare(strict_types=1);

use DemonDextralHorn\Enums\AuthDriverType;
use DemonDextralHorn\Enums\OrderType;
use DemonDextralHorn\Resolvers\Strategies\Source\ForwardValueStrategy;
use DemonDextralHorn\Resolvers\Strategies\Source\ResponseJwtStrategy;
use DemonDextralHorn\Resolvers\Strategies\Source\ResponsePluckStrategy;
use DemonDextralHorn\Resolvers\Strategies\Source\ResponseSessionHeaderStrategy;
use DemonDextralHorn\Resolvers\Strategies\Source\ResponseValueStrategy;
use DemonDextralHorn\Resolvers\Strategies\Transform\IncrementStrategy;
use Illuminate\Http\Request;

return [
    'defaults' => [
        'enabled' => env('PREFETCH_ENABLED', false), // Enable/disable prefetching, default is disabled
        'cache_ttl' => env('PREFETCH_CACHE_TTL', 60),
        'cache_prefix' => env('PREFETCH_CACHE_PREFIX', 'demon_dextral_horn'),
        'cache_max_size' => env('PREFETCH_CACHE_MAX_SIZE', 1048576), // 1 MB = 1024 * 1024 bytes = 1048576 bytes
        'queue_connection' => env('PREFETCH_QUEUE_CONNECTION', 'redis'),
        'queue_name' => env('PREFETCH_QUEUE_NAME', 'prefetch'),
        'prefetch_header' => env('PREFETCH_HEADER', 'Demon-Prefetch-Call'),
        'auth_driver' => config('auth.guards.api.driver', AuthDriverType::SESSION->value), // It can get session or jwt at the moment (values of AuthDriverType)
        'session_cookie_name' => config('session.cookie', 'laravel_session'),
    ],
    'rules' => [
        /**
         * Sample rule logic for showcasing different prefetching strategies/scenarios.
         */
        [
            'id' => 'sample_rule_id',
            'description' => 'Sample/generalized rule description for prefetching related data',
            'trigger' => [
                'method' => Request::METHOD_GET,
                'route' => 'sample.trigger.route.name',
            ],
            'targets' => [
                /**
                 * Sample target route with only query parameter, increment strategy will be applied.
                 * e.g. /api/sample?page=1
                 */
                [
                    'method' => Request::METHOD_GET,
                    'route' => 'sample.target.route.with.only.query.param',
                    'query_params' => [
                        'query_key' => [
                            'strategy' => IncrementStrategy::class,
                            'options' => [
                                'key' => 'query_key',
                                'increment' => 1,
                            ],
                        ],
                    ],
                ],

                /**
                 * Sample target route with only query parameter, forward value strategy will be applied.
                 * e.g. /api/sample?query_trigger_key=value will be /api/sample?query_target_key=value
                 * This also showcase that the query parameter can be renamed during the forwarding process.
                 */
                [
                    'method' => Request::METHOD_GET,
                    'route' => 'sample.target.route.with.forward.value',
                    'query_params' => [
                        'query_trigger_key' => [
                            'strategy' => ForwardValueStrategy::class,
                            'options' => [
                                'key' => 'query_target_key',
                            ],
                        ],
                    ],
                ],

                /**
                 * Sample target route without parameters, so no strategy is needed for resolving the parameters.
                 * e.g. /api/sample
                 */
                [
                    'method' => Request::METHOD_GET,
                    'route' => 'sample.target.route.without.params',
                ],

                /**
                 * Sample target route with route parameter, strategy will be applied to resolve the route parameter from the trigger response data.
                 * e.g. /api/sample/{route_key} where route_key is extracted from the response data.id
                 */
                [
                    'method' => Request::METHOD_GET,
                    'route' => 'sample.target.route.with.route.param',
                    'route_params' => [
                        'route_key' => [
                            'strategy' => ResponseValueStrategy::class,
                            'options' => [
                                'key' => 'route_key',
                                'position' => 'data.id', // Full path to the value in the response data
                            ],
                        ],
                    ],
                ],

                /**
                 * Sample target route with route parameter, strategy will be applied to resolve multiple route parameters from the trigger response data.
                 * e.g. /api/trigger where first 3 ids are extracted/plucked from the response data collection and target route (e.g. api/target/{route_key}) will be dispatched with these 3 ids - route will be called 3 times with each ids separately.
                 */
                [
                    'method' => Request::METHOD_GET,
                    'route' => 'sample.target.route.with.pluck.dispatch',
                    'route_params' => [
                        'route_key' => [
                            'strategy' => ResponsePluckStrategy::class,
                            'options' => [
                                'key' => 'route_key',
                                'position' => 'data.*.id',
                                'limit' => 3,
                                'order' => OrderType::DESC->value,
                            ],
                        ],
                    ],
                ],
            ],
        ],

        /**
         * Sample rule logic for handling login requests including JWT token extraction, laravel session handling etc.
         */
        [
            'id' => 'login_request_id',
            'description' => 'Handle login request and prefetch data for authenticated user',
            'trigger' => [
                'method' => Request::METHOD_POST,
                'route' => 'auth.login',
            ],
            'targets' => [
                /**
                 * Sample target route with authorization header regarding JWT token.
                 * The Bearer token will be extracted from the response data and added to the authorization header.
                 */
                [
                    'method' => Request::METHOD_GET,
                    'route' => 'sample.target.route.requires.token.from.login.request',
                    'headers' => [
                        'authorization' => [
                            'strategy' => ResponseJwtStrategy::class,
                            'options' => [
                                'key' => 'authorization',
                                'position' => 'data.access_token',
                            ],
                        ],
                    ],
                ],

                /**
                 * Sample target route with laravel session cookie.
                 * The session cookie will be extracted from the response headers (Set-Cookie) and set in the request cookies.
                 */
                [
                    'method' => Request::METHOD_GET,
                    'route' => 'sample.target.route.with.session.cookie',
                    'cookies' => [
                        'session_cookie' => [
                            'strategy' => ResponseSessionHeaderStrategy::class,
                            'options' => [
                                'key' => config('session.cookie', 'laravel_session'),
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
