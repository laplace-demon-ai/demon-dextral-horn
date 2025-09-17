<?php

declare(strict_types=1);

namespace Tests\Resolvers;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Illuminate\Http\Request;
use DemonDextralHorn\Resolvers\QueryParamResolver;
use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Resolvers\Strategies\Transform\IncrementStrategy;
use DemonDextralHorn\Resolvers\Strategies\Source\ForwardValueStrategy;
use DemonDextralHorn\Exceptions\MissingStrategyOptionException;

#[CoversClass(QueryParamResolver::class)]
final class QueryParamResolverTest extends TestCase
{
    private QueryParamResolver $resolver;
    private ?RequestData $requestData = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->resolver = app(QueryParamResolver::class);
    }

    #[Test]
    public function it_resolves_query_parameters_for_increment_strategy(): void
    {
        /* SETUP */
        $queryKey = 3;
        $increment = 2;
        $request = Request::create(
            uri: "/sample_trigger_route?query_key={$queryKey}",
            method: Request::METHOD_GET,
        );
        $this->requestData = RequestData::fromRequest($request);
        $routeDefinition = [
            'method' => Request::METHOD_GET,
            'route' => 'sample.target.route.with.only.query.param',
            'query_params' => [
                'query_key' => [
                    'strategy' => IncrementStrategy::class,
                    'options' => [
                        'source_key' => 'query_key',
                        'increment' => $increment,
                    ],
                ],
            ],
        ]; // Route definition for the increment strategy

        /* EXECUTE */
        $resolvedParams = $this->resolver->resolve(targetRouteDefinition: $routeDefinition, requestData: $this->requestData);

        /* ASSERT */
        $this->assertEquals(['query_key' => $queryKey + $increment], $resolvedParams);
    }

    #[Test]
    public function it_returns_empty_array_when_no_query_params_defined(): void
    {
        /* SETUP */
        $request = Request::create('/sample_trigger_route', Request::METHOD_GET);
        $this->requestData = RequestData::fromRequest($request);
        $routeDefinition = [
            'method' => Request::METHOD_GET,
            'route' => 'sample.target.route.without.query.param',
        ];

        /* EXECUTE */
        $resolvedParams = $this->resolver->resolve(
            targetRouteDefinition: $routeDefinition,
            requestData: $this->requestData
        );

        /* ASSERT */
        $this->assertEmpty($resolvedParams);
    }

    #[Test]
    public function it_can_resolve_multiple_query_parameters(): void
    {
        /* SETUP */
        $first = 1;
        $second = 5;
        $incrementFirst = 10;
        $incrementSecond = 15;
        $request = Request::create("/route?first={$first}&second={$second}", Request::METHOD_GET);
        $this->requestData = RequestData::fromRequest($request);
        $routeDefinition = [
            'method' => Request::METHOD_GET,
            'route' => 'sample.target.route.with.multiple.query.params',
            'query_params' => [
                'first' => [
                    'strategy' => IncrementStrategy::class,
                    'options' => ['source_key' => 'first', 'increment' => $incrementFirst],
                ],
                'second' => [
                    'strategy' => IncrementStrategy::class,
                    'options' => ['source_key' => 'second', 'increment' => $incrementSecond],
                ],
            ],
        ];

        /* EXECUTE */
        $resolvedParams = $this->resolver->resolve(
            targetRouteDefinition: $routeDefinition,
            requestData: $this->requestData
        );

        /* ASSERT */
        $this->assertEquals(['first' => $incrementFirst + $first, 'second' => $incrementSecond + $second], $resolvedParams);
    }

    #[Test]
    public function it_can_resolve_query_parameters_for_forward_value_strategy(): void
    {
        /* SETUP */
        $queryKey = 3;
        $request = Request::create(
            uri: "/sample_trigger_route?query_trigger_key={$queryKey}",
            method: Request::METHOD_GET,
        );
        $this->requestData = RequestData::fromRequest($request);
        $routeDefinition = [
            'method' => Request::METHOD_GET,
            'route' => 'sample.target.route.with.only.query.param',
            'query_params' => [
                'query_target_key' => [
                    'strategy' => ForwardValueStrategy::class,
                    'options' => [
                        'source_key' => 'query_trigger_key',
                    ],
                ],
            ],
        ];

        /* EXECUTE */
        $resolvedParams = $this->resolver->resolve(targetRouteDefinition: $routeDefinition, requestData: $this->requestData);

        /* ASSERT */
        $this->assertEquals(['query_target_key' => $queryKey], $resolvedParams);
    }

    #[Test]
    public function it_can_resolve_array_query_parameters_for_forward_value_strategy(): void
    {
        /* SETUP */
        $firstItem = 1;
        $secondItem = 2;
        $thirdItem = 3;
        $request = Request::create("/route?items[]={$firstItem}&items[]={$secondItem}&items[]={$thirdItem}", Request::METHOD_GET);
        $this->requestData = RequestData::fromRequest($request);
        $routeDefinition = [
            'method' => Request::METHOD_GET,
            'route' => 'sample.target.route.with.array.query.params',
            'query_params' => [
                'items' => [
                    'strategy' => ForwardValueStrategy::class,
                    'options' => ['source_key' => 'items'],
                ],
            ],
        ];

        /* EXECUTE */
        $resolvedParams = $this->resolver->resolve(
            targetRouteDefinition: $routeDefinition,
            requestData: $this->requestData
        );

        /* ASSERT */
        $this->assertEquals(['items' => [$firstItem, $secondItem, $thirdItem]], $resolvedParams);
    }

    #[Test]
    public function it_forwards_query_param_without_transoformation(): void
    {
        /* SETUP */
        $queryKey = 3;
        $request = Request::create(
            uri: "/sample_trigger_route?query_key={$queryKey}",
            method: Request::METHOD_GET,
        );
        $this->requestData = RequestData::fromRequest($request);
        $routeDefinition = [
            'method' => Request::METHOD_GET,
            'route' => 'sample.target.route.with.only.query.param',
            'query_params' => [
                'query_key' => [
                    'strategy' => ForwardValueStrategy::class,
                    'options' => [
                        'source_key' => 'query_key',
                    ],
                ],
            ],
        ]; // Route definition for the increment strategy

        /* EXECUTE */
        $resolvedParams = $this->resolver->resolve(targetRouteDefinition: $routeDefinition, requestData: $this->requestData);

        /* ASSERT */
        $this->assertEquals(['query_key' => $queryKey], $resolvedParams);
    }

    #[Test]
    public function it_throws_missing_strategy_option_exception_when_query_key_missing(): void
    {
        /* SETUP */
        $request = Request::create(
            uri: "/sample_trigger_route",
            method: Request::METHOD_GET,
        );
        $this->requestData = RequestData::fromRequest($request);
        $routeDefinition = [
            'method' => Request::METHOD_GET,
            'route' => 'sample.target.route.with.only.query.param',
            'query_params' => [
                'query_key' => [
                    'strategy' => ForwardValueStrategy::class,
                    'options' => [
                        'source_key' => 'query_key',
                    ],
                ],
            ],
        ]; // Route definition for the increment strategy
        $this->expectException(MissingStrategyOptionException::class);

        /* EXECUTE */
        $this->resolver->resolve(targetRouteDefinition: $routeDefinition, requestData: $this->requestData);
    }
}