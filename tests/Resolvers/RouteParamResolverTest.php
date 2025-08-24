<?php

declare(strict_types=1);

namespace Tests\Resolvers;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use DemonDextralHorn\Resolvers\RouteParamResolver;
use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Exceptions\MissingStrategyOptionException;
use DemonDextralHorn\Resolvers\Strategies\Source\ResponseValueStrategy;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use TypeError;

/**
 * Test for RouteParamResolver.
 * 
 * @class RouteParamResolverTest
 */
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
        $data = ['id' => $id];
        $request = Request::create(
            uri: "/sample_trigger_route",
            method: Request::METHOD_GET,
        );
        $this->requestData = RequestData::fromRequest($request);
        $response = new Response(json_encode(['data' => $data]), 200);
        $this->responseData = ResponseData::fromResponse($response);
        $routeDefinition = [
            'method' => Request::METHOD_GET,
            'route' => 'sample.target.route.with.route.param',
            'route_params' => [
                'route_key' => [
                    'strategy' => ResponseValueStrategy::class,
                    'options' => [
                        'key' => 'route_key',
                        'position' => 'data.id',
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
        $this->assertArrayHasKey('route_key', $result);
        $this->assertEquals($id, $result['route_key']);
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
        $response = new Response(json_encode(['data' => null]), 200);
        $responseData = ResponseData::fromResponse($response);
        $routeDefinition = [
            'method' => Request::METHOD_GET,
            'route' => 'sample.target.route.with.route.param',
            'route_params' => [
                'route_key' => [
                    'strategy' => ResponseValueStrategy::class,
                    'options' => [
                        'key' => 'route_key',
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
        $this->assertArrayHasKey('route_key', $result);
        $this->assertNull($result['route_key']);
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
        $this->assertIsArray($result);
        $this->assertEmpty($result);
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
        $response = new Response(json_encode(['data' => ['id' => $id, 'details' => ['name' => $name]]]), 200);
        $responseData = ResponseData::fromResponse($response);
        $routeDefinition = [
            'method' => Request::METHOD_GET,
            'route' => 'sample.route.with.multiple.params',
            'route_params' => [
                'id_key' => [
                    'strategy' => ResponseValueStrategy::class,
                    'options' => [
                        'key' => 'id_key',
                        'position' => 'data.id',
                    ],
                ],
                'name_key' => [
                    'strategy' => ResponseValueStrategy::class,
                    'options' => [
                        'key' => 'name_key',
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
        $this->assertSame($id, Arr::get($result, 'id_key'));
        $this->assertSame($name, Arr::get($result, 'name_key'));
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
    public function it_throws_invalid_argument_exception_when_option_key_is_not_existent_for_response_value_strategy(): void
    {
        /* SETUP */
        $routeDefinition = [
            'method' => Request::METHOD_GET,
            'route' => 'sample.route.invalid',
            'route_params' => [
                'invalid_key' => [
                    'strategy' => ResponseValueStrategy::class,
                    'options' => [
                        'position' => 'data.id',
                    ],
                ],
            ],
        ];
        $this->expectException(MissingStrategyOptionException::class);
        $this->expectExceptionMessage('ResponseValueStrategy requires the "key/position" option.');

        /* EXECUTE */
        $this->resolver->resolve(
            targetRouteDefinition: $routeDefinition,
        );
    }

    #[Test]
    public function it_throws_invalid_argument_exception_when_option_position_is_not_existent_for_response_value_strategy(): void
    {
        /* SETUP */
        $routeDefinition = [
            'method' => Request::METHOD_GET,
            'route' => 'sample.route.invalid',
            'route_params' => [
                'invalid_key' => [
                    'strategy' => ResponseValueStrategy::class,
                    'options' => [
                        'key' => 'invalid_key',
                    ],
                ],
            ],
        ];
        $this->expectException(MissingStrategyOptionException::class);
        $this->expectExceptionMessage('ResponseValueStrategy requires the "key/position" option.');

        /* EXECUTE */
        $this->resolver->resolve(
            targetRouteDefinition: $routeDefinition,
        );
    }
}