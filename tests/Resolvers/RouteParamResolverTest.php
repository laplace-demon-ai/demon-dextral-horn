<?php

declare(strict_types=1);

namespace Tests\Resolvers;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use DemonDextralHorn\Resolvers\RouteParamResolver;
use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Exceptions\MissingStrategyOptionException;
use DemonDextralHorn\Resolvers\Strategies\Source\ResponseValueStrategy;
use DemonDextralHorn\Resolvers\Strategies\Source\ResponsePluckStrategy;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use TypeError;

#[CoversClass(RouteParamResolver::class)]
final class RouteParamResolverTest extends TestCase
{
    private RouteParamResolver $resolver;
    private ?RequestData $requestData = null;
    private ?ResponseData $responseData = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->resolver = app(RouteParamResolver::class);
    }

    #[Test]
    public function it_tests_response_value_strategy_when_route_key_is_present(): void
    {
        /* SETUP */
        $id = 32;
        $name = 'Sample Name';
        $data = ['id' => $id, 'name' => $name];
        $request = Request::create(
            uri: "/sample_trigger_route",
            method: Request::METHOD_GET,
        );
        $this->requestData = RequestData::fromRequest($request);
        $response = new Response(json_encode(['data' => $data]), Response::HTTP_OK);
        $this->responseData = ResponseData::fromResponse($response);
        $routeDefinition = [
            'method' => Request::METHOD_GET,
            'route' => 'sample.target.route.with.route.param',
            'route_params' => [
                'route_key' => [
                    'strategy' => ResponseValueStrategy::class,
                    'options' => [
                        'source_key' => 'route_key',
                        'position' => 'data.id',
                    ],
                ],
                'other_key' => [
                    'strategy' => ResponseValueStrategy::class,
                    'options' => [
                        'source_key' => 'other_key',
                        'position' => 'data.name',
                    ],
                ],
            ],
        ];

        /* EXECUTE */
        $result = $this->resolver->resolve(
            targetRouteDefinition: $routeDefinition,
            requestData: $this->requestData,
            responseData: $this->responseData
        );

        /* ASSERT */
        $this->assertArrayHasKey('route_key', $result[0]);
        $this->assertEquals($id, $result[0]['route_key']);
        $this->assertArrayHasKey('other_key', $result[0]);
        $this->assertEquals($name, $result[0]['other_key']);
    }

    #[Test]
    public function it_tests_response_pluck_strategy_where_multiple_results_return(): void
    {
        /* SETUP */
        $firstId = 13;
        $secondId = 22;
        $thirdId = 23;
        $data = [['id' => $firstId], ['id' => $secondId], ['id' => $thirdId]];
        $request = Request::create(
            uri: "/sample_trigger_route",
            method: Request::METHOD_GET,
        );
        $this->requestData = RequestData::fromRequest($request);
        $response = new Response(json_encode(['data' => $data]), Response::HTTP_OK);
        $this->responseData = ResponseData::fromResponse($response);
        $routeDefinition = [
            'method' => Request::METHOD_GET,
            'route' => 'sample.target.route.with.pluck.dispatch',
            'route_params' => [
                'route_key' => [
                    'strategy' => ResponsePluckStrategy::class,
                    'options' => [
                        'position' => 'data.*.id',
                    ],
                ],
            ],
        ];

        /* EXECUTE */
        $result = $this->resolver->resolve(
            targetRouteDefinition: $routeDefinition,
            requestData: $this->requestData,
            responseData: $this->responseData
        );

            /* ASSERT */;
        $this->assertEquals($firstId, Arr::get($result[0], 'route_key'));
        $this->assertEquals($secondId, Arr::get($result[1], 'route_key'));
        $this->assertEquals($thirdId, Arr::get($result[2], 'route_key'));
    }

    #[Test]
    public function it_tests_response_pluck_strategy_where_all_cartesian_results_return(): void
    {
        /* SETUP */
        $firstId = 13;
        $secondId = 22;
        $thirdId = 23;
        $firstName = 'First';
        $secondName = 'Second';
        $thirdName = 'Third';
        $data = [['id' => $firstId, 'name' => $firstName], ['id' => $secondId, 'name' => $secondName], ['id' => $thirdId, 'name' => $thirdName]];
        $request = Request::create(
            uri: "/sample_trigger_route",
            method: Request::METHOD_GET,
        );
        $this->requestData = RequestData::fromRequest($request);
        $response = new Response(json_encode(['data' => $data]), Response::HTTP_OK);
        $this->responseData = ResponseData::fromResponse($response);
        $routeDefinition = [
            'method' => Request::METHOD_GET,
            'route' => 'sample.target.route.with.pluck.dispatch',
            'route_params' => [
                'route_key' => [
                    'strategy' => ResponsePluckStrategy::class,
                    'options' => [
                        'position' => 'data.*.id',
                    ],
                ],
                'other_key' => [
                    'strategy' => ResponsePluckStrategy::class,
                    'options' => [
                        'position' => 'data.*.name',
                    ],
                ],
            ],
        ];

        /* EXECUTE */
        $result = $this->resolver->resolve(
            targetRouteDefinition: $routeDefinition,
            requestData: $this->requestData,
            responseData: $this->responseData
        );

        /* ASSERT */
        $this->assertEquals($firstId, Arr::get($result[0], 'route_key'));
        $this->assertEquals($firstName, Arr::get($result[0], 'other_key'));
        $this->assertEquals($firstId, Arr::get($result[1], 'route_key'));
        $this->assertEquals($secondName, Arr::get($result[1], 'other_key'));
        $this->assertEquals($firstId, Arr::get($result[2], 'route_key'));
        $this->assertEquals($thirdName, Arr::get($result[2], 'other_key'));
        $this->assertEquals($secondId, Arr::get($result[3], 'route_key'));
        $this->assertEquals($firstName, Arr::get($result[3], 'other_key'));
        $this->assertEquals($secondId, Arr::get($result[4], 'route_key'));
        $this->assertEquals($secondName, Arr::get($result[4], 'other_key'));
        $this->assertEquals($secondId, Arr::get($result[5], 'route_key'));
        $this->assertEquals($thirdName, Arr::get($result[5], 'other_key'));
        $this->assertEquals($thirdId, Arr::get($result[6], 'route_key'));
        $this->assertEquals($firstName, Arr::get($result[6], 'other_key'));
        $this->assertEquals($thirdId, Arr::get($result[7], 'route_key'));
        $this->assertEquals($secondName, Arr::get($result[7], 'other_key'));
        $this->assertEquals($thirdId, Arr::get($result[8], 'route_key'));
        $this->assertEquals($thirdName, Arr::get($result[8], 'other_key'));
    }

    #[Test]
    public function it_tests_response_value_strategy_when_route_key_is_present_and_strategy_returns_null(): void
    {
        /* SETUP */
        $request = Request::create(
            uri: "/sample_endpoint",
            method: Request::METHOD_GET,
        );
        $requestData = RequestData::fromRequest($request);
        $response = new Response(json_encode(['data' => null]), Response::HTTP_OK);
        $responseData = ResponseData::fromResponse($response);
        $routeDefinition = [
            'method' => Request::METHOD_GET,
            'route' => 'sample.target.route.with.route.param',
            'route_params' => [
                'route_key' => [
                    'strategy' => ResponseValueStrategy::class,
                    'options' => [
                        'position' => 'data.id',
                    ],
                ],
            ],
        ];

        /* EXECUTE */
        $result = $this->resolver->resolve(
            targetRouteDefinition: $routeDefinition,
            requestData: $requestData,
            responseData: $responseData
        );

        /* ASSERT */
        $this->assertArrayHasKey('route_key', $result[0]);
        $this->assertNull($result[0]['route_key']);
    }

    #[Test]
    public function it_returns_empty_array_when_no_route_params_are_defined(): void
    {
        /* SETUP */
        $routeDefinition = [
            'method' => Request::METHOD_GET,
            'route' => 'sample.target.route.without.params',
        ];

        /* EXECUTE */
        $result = $this->resolver->resolve($routeDefinition);

        /* ASSERT */
        $this->assertIsArray($result[0]);
        $this->assertEmpty($result[0]);
    }

    #[Test]
    public function it_resolves_multiple_params_with_different_strategies(): void
    {
        /* SETUP */
        $id = 100;
        $name = 'Dextral';
        $request = Request::create(
            uri: "/sample_endpoint",
            method: Request::METHOD_GET,
        );
        $requestData = RequestData::fromRequest($request);
        $response = new Response(json_encode(['data' => ['id' => $id, 'details' => ['name' => $name]]]), Response::HTTP_OK);
        $responseData = ResponseData::fromResponse($response);
        $routeDefinition = [
            'method' => Request::METHOD_GET,
            'route' => 'sample.route.with.multiple.params',
            'route_params' => [
                'id_key' => [
                    'strategy' => ResponseValueStrategy::class,
                    'options' => [
                        'source_key' => 'id_key',
                        'position' => 'data.id',
                    ],
                ],
                'name_key' => [
                    'strategy' => ResponseValueStrategy::class,
                    'options' => [
                        'source_key' => 'name_key',
                        'position' => 'data.details.name',
                    ],
                ],
            ],
        ];

        /* EXECUTE */
        $result = $this->resolver->resolve(
            $routeDefinition,
            $requestData,
            $responseData
        );

        /* ASSERT */
        $this->assertSame($id, Arr::get($result[0], 'id_key'));
        $this->assertSame($name, Arr::get($result[0], 'name_key'));
    }

    #[Test]
    public function it_throws_when_strategy_is_missing(): void
    {
        /* SETUP */
        $routeDefinition = [
            'method' => Request::METHOD_GET,
            'route' => 'sample.route.invalid',
            'route_params' => [
                'invalid_key' => [
                    'strategy' => null,
                ],
            ],
        ];
        $this->expectException(TypeError::class);

        /* EXECUTE */
        $this->resolver->resolve(
            targetRouteDefinition: $routeDefinition,
        );
    }

    #[Test]
    public function it_throws_invalid_argument_exception_when_strategy_is_not_existent(): void
    {
        /* SETUP */
        $routeDefinition = [
            'method' => Request::METHOD_GET,
            'route' => 'sample.route.invalid',
            'route_params' => [
                'invalid_key' => [
                    'strategy' => 'NonExistentStrategy',
                ],
            ],
        ];
        $this->expectException(InvalidArgumentException::class);

        /* EXECUTE */
        $this->resolver->resolve(
            targetRouteDefinition: $routeDefinition,
        );
    }

    #[Test]
    public function it_throws_invalid_argument_exception_when_option_position_is_not_existent_for_response_value_strategy(): void
    {
        /* SETUP */
        $strategy = ResponseValueStrategy::class;
        $routeDefinition = [
            'method' => Request::METHOD_GET,
            'route' => 'sample.route.invalid',
            'route_params' => [
                'invalid_key' => [
                    'strategy' => $strategy,
                    'options' => [],
                ],
            ],
        ];
        $this->expectException(MissingStrategyOptionException::class);
        $this->expectExceptionMessage($strategy . ' requires the "position" option.');

        /* EXECUTE */
        $this->resolver->resolve(
            targetRouteDefinition: $routeDefinition,
        );
    }

    #[Test]
    public function it_returns_empty_array_when_strategy_returns_empty_array(): void
    {
        /* SETUP */
        $request = Request::create('/sample', Request::METHOD_GET);
        $requestData = RequestData::fromRequest($request);
        $response = new Response(json_encode(['data' => []]), Response::HTTP_OK);
        $responseData = ResponseData::fromResponse($response);
        $routeDefinition = [
            'method' => Request::METHOD_GET,
            'route' => 'sample.route.empty',
            'route_params' => [
                'route_key' => [
                    'strategy' => ResponsePluckStrategy::class,
                    'options' => [
                        'position' => 'data.*.id',
                    ],
                ],
            ],
        ];

        /* EXECUTE */
        $result = $this->resolver->resolve(
            targetRouteDefinition: $routeDefinition,
            requestData: $requestData,
            responseData: $responseData
        );

        /* ASSERT */
        $this->assertSame([], $result);
    }

    #[Test]
    public function it_keeps_null_values_in_expanded_results(): void
    {
        /* SETUP */
        $id = 42;
        $request = Request::create('/sample', Request::METHOD_GET);
        $requestData = RequestData::fromRequest($request);
        $response = new Response(json_encode(['data' => ['id' => $id]]), Response::HTTP_OK);
        $responseData = ResponseData::fromResponse($response);
        $routeDefinition = [
            'method' => Request::METHOD_GET,
            'route' => 'sample.route.with.nulls',
            'route_params' => [
                'id_key' => [
                    'strategy' => ResponseValueStrategy::class,
                    'options' => [
                        'source_key' => 'id_key',
                        'position' => 'data.id',
                    ],
                ],
                'null_key' => [
                    'strategy' => ResponseValueStrategy::class,
                    'options' => [
                        'source_key' => 'null_key',
                        'position' => 'data.nonexistent',
                    ],
                ],
            ],
        ];

        /* EXECUTE */
        $result = $this->resolver->resolve(
            targetRouteDefinition: $routeDefinition,
            requestData: $requestData,
            responseData: $responseData
        );

        /* ASSERT */
        $this->assertCount(1, $result);
        $this->assertSame($id, $result[0]['id_key']);
        $this->assertNull($result[0]['null_key']);
    }

    #[Test]
    public function it_tests_from_request_sets_route_name_and_params_correctly(): void
    {
        /* SETUP */
        $request = Request::create('/value1/value2/sample-target-route-success/value3/value4', Request::METHOD_GET);
        $request->setRouteResolver(fn () => $this->app['router']->getRoutes()->match($request));

        /* EXECUTE */
        $requestData = RequestData::fromRequest($request);

        /* ASSERT */
        $this->assertSame('sample.target.route.name', $requestData->routeName);
        $this->assertSame([
            'param1' => 'value1',
            'param2' => 'value2',
            'param3' => 'value3',
            'param4' => 'value4',
        ], $requestData->routeParams);
        $this->assertSame('/value1/value2/sample-target-route-success/value3/value4', $requestData->uri);
        $this->assertSame(Request::METHOD_GET, $requestData->method);
    }
}