<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use DemonDextralHorn\Resolvers\Strategies\Transform\IncrementStrategy;

return [
    'defaults' => [
        'cache_ttl' => env('PREFETCH_CACHE_TTL', 60),
        'cache_prefix' => env('PREFETCH_CACHE_PREFIX', 'demon_dextral_horn:'),
        'queue_connection' => env('PREFETCH_QUEUE_CONNECTION', 'redis'),
        'queue_name' => env('PREFETCH_QUEUE_NAME', 'prefetch'),
        'prefetch_header' => env('PREFETCH_HEADER', 'Demon-Prefetch-Call'),
    ],
    'rules' => [
        [
            'id' => 'sample_rule_id',
            'description' => 'Sample/generalized rule description for prefetching related data',
            'trigger' => [
                'method' => Request::METHOD_GET,
                'route' => 'sample.trigger.route.name'
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
                 * Sample target route without parameters, so no strategy is needed for resolving the parameters.
                 * e.g. /api/sample
                 */
                [
                    'method' => Request::METHOD_GET,
                    'route' => 'sample.target.route.without.params'
                ]
            ],
        ],
    ],
];