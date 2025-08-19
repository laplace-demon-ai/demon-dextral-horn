<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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
                [
                    'method' => Request::METHOD_GET,
                    'route' => 'sample.target.route.name',
                    'route_params' => [
                        'param1' => fn(Request $req, Response $res) => data_get(json_decode($res->getContent(), true), 'data.response_param1'), // derive from response
                        'param2' => fn(Request $req, Response $res) => $req->route('param2'), // derive from route
                        'param3' => fn(Request $req, Response $res) => $req->input('param3'), // derive from request input
                        'param4' => fn(Request $req, Response $res) => auth()->id, // derive from auth
                    ],
                    'query_params' => [
                        'query_param1' => fn(Request $req, Response $res) => $req->query('param1'),  // derive from query param, and will be used on query param
                    ],
                ],
            ]
        ],
    ],
];