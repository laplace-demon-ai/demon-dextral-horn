<?php

declare(strict_types=1);

namespace Tests\Resolvers;

use DemonDextralHorn\Resolvers\TargetRouteResolver;
use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\HeadersData;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;

#[CoversClass(TargetRouteResolver::class)]
final class TargetRouteResolverTest extends TestCase
{
    private TargetRouteResolver $resolver;

    public function setUp(): void
    {
        parent::setUp();

        $this->resolver = app(TargetRouteResolver::class);
    }

    #[Test]
    public function it_returns_targets_when_matching_rule_found(): void
    {
        /* SETUP */
        $firstTargetRoute = 'sample.target.first';
        $secondTargetRoute = 'sample.target.second';
        $triggerRoute = 'sample.trigger.route';
        Config::set('demon-dextral-horn.rules', [
            [
                'trigger' => [
                    'route' => $triggerRoute,
                    'method' => Request::METHOD_GET,
                ],
                'targets' => [
                    ['route' => $firstTargetRoute],
                    ['route' => $secondTargetRoute],
                ],
            ],
        ]);
        $requestData = new RequestData(
            uri: '/sample_trigger_route',
            method: Request::METHOD_GET,
            headers: new HeadersData(),
            payload: [],
            routeName: $triggerRoute
        );

        /* EXECUTE */
        $result = $this->resolver->resolve(
            requestData: $requestData
        );

        /* ASSERT */
        $this->assertEquals(
            [
                ['route' => $firstTargetRoute],
                ['route' => $secondTargetRoute],
            ],
            $result
        );
    }

    #[Test]
    public function it_returns_empty_array_when_no_rules_configured(): void
    {
        /* SETUP */
        Config::set('demon-dextral-horn.rules', []);
        $requestData = new RequestData(
            uri: '/no_rules',
            method: Request::METHOD_GET,
            headers: new HeadersData(),
            payload: [],
            routeName: 'nonexistent.route'
        );

        /* EXECUTE */
        $result = $this->resolver->resolve(requestData: $requestData);

        /* ASSERT */
        $this->assertEmpty($result);
    }

    #[Test]
    public function it_returns_empty_array_when_route_name_does_not_match(): void
    {
        /* SETUP */
        Config::set('demon-dextral-horn.rules', [
            [
                'trigger' => [
                    'route' => 'other.route',
                    'method' => Request::METHOD_GET,
                ],
                'targets' => [
                    ['route' => 'some.target'],
                ],
            ],
        ]);
        $requestData = new RequestData(
            uri: '/sample',
            method: Request::METHOD_GET,
            headers: new HeadersData(),
            payload: [],
            routeName: 'sample.trigger.route'
        );

        /* EXECUTE */
        $result = $this->resolver->resolve(requestData: $requestData);

        /* ASSERT */
        $this->assertEmpty($result);
    }

    #[Test]
    public function it_returns_empty_array_when_request_data_is_null(): void
    {
        /* SETUP */
        Config::set('demon-dextral-horn.rules', [
            [
                'trigger' => [
                    'route' => 'any.route',
                    'method' => Request::METHOD_GET,
                ],
                'targets' => [
                    ['route' => 'some.target'],
                ],
            ],
        ]);

        /* EXECUTE */
        $result = $this->resolver->resolve(requestData: null);

        /* ASSERT */
        $this->assertEmpty($result);
    }
}