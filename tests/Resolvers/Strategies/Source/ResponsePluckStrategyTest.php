<?php

declare(strict_types=1);

namespace Tests\Resolvers\Strategies\Transform;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use DemonDextralHorn\Resolvers\Strategies\Source\ResponsePluckStrategy;
use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Enums\OrderType;
use DemonDextralHorn\Exceptions\MissingStrategyOptionException;

#[CoversClass(ResponsePluckStrategy::class)]
final class ResponsePluckStrategyTest extends TestCase
{
    private ResponsePluckStrategy $responsePluckStrategy;

    public function setUp(): void
    {
        parent::setUp();

        $this->responsePluckStrategy = app(ResponsePluckStrategy::class);
    }

    #[Test]
    public function it_tests_response_pluck_strategy_when_route_key_is_present_and_response_data_is_array(): void
    {
        /* SETUP */
        $firstId = 32;
        $secondId = 33;
        $thirdId = 34;
        $data = [
            ['id' => $firstId],
            ['id' => $secondId],
            ['id' => $thirdId],
        ];
        $request = Request::create(
            uri: "/sample_endpoint",
            method: Request::METHOD_GET,
        );
        $requestData = RequestData::fromRequest($request);
        $response = new Response(json_encode(['data' => $data]), Response::HTTP_OK);
        $responseData = ResponseData::fromResponse($response);

        /* EXECUTE */
        $result = $this->responsePluckStrategy->handle(
            requestData: $requestData,
            responseData: $responseData,
            options: [
                'position' => 'data.*.id'
            ]
        );

        /* ASSERT */
        $this->assertEquals([$firstId, $secondId, $thirdId], $result);
    }

    #[Test]
    public function it_tests_response_pluck_strategy_with_limit(): void
    {
        /* SETUP */
        $firstId = 32;
        $secondId = 33;
        $thirdId = 34;
        $limit = 2;
        $data = [
            ['id' => $firstId],
            ['id' => $secondId],
            ['id' => $thirdId],
        ];
        $request = Request::create(
            uri: "/sample_endpoint",
            method: Request::METHOD_GET,
        );
        $requestData = RequestData::fromRequest($request);
        $response = new Response(json_encode(['data' => $data]), Response::HTTP_OK);
        $responseData = ResponseData::fromResponse($response);

        /* EXECUTE */
        $result = $this->responsePluckStrategy->handle(
            requestData: $requestData,
            responseData: $responseData,
            options: [
                'position' => 'data.*.id',
                'limit' => $limit,
            ]
        );

        /* ASSERT */
        $this->assertEquals([$firstId, $secondId], $result);
        $this->assertNotContains($thirdId, $result);
    }

    #[Test]
    public function it_tests_response_pluck_strategy_with_order(): void
    {
        /* SETUP */
        $firstId = 32;
        $secondId = 33;
        $thirdId = 34;
        $data = [
            ['id' => $firstId],
            ['id' => $secondId],
            ['id' => $thirdId],
        ];
        $request = Request::create(
            uri: "/sample_endpoint",
            method: Request::METHOD_GET,
        );
        $requestData = RequestData::fromRequest($request);
        $response = new Response(json_encode(['data' => $data]), Response::HTTP_OK);
        $responseData = ResponseData::fromResponse($response);

        /* EXECUTE */
        $result = $this->responsePluckStrategy->handle(
            requestData: $requestData,
            responseData: $responseData,
            options: [
                'position' => 'data.*.id',
                'order' => OrderType::DESC->value
            ]
        );

        /* ASSERT */
        $this->assertEquals([$thirdId, $secondId, $firstId], $result);
    }

    #[Test]
    public function it_tests_response_pluck_strategy_with_limit_and_order(): void
    {
        /* SETUP */
        $firstId = 32;
        $secondId = 33;
        $thirdId = 34;
        $limit = 2;
        $data = [
            ['id' => $firstId],
            ['id' => $secondId],
            ['id' => $thirdId],
        ];
        $request = Request::create(
            uri: "/sample_endpoint",
            method: Request::METHOD_GET,
        );
        $requestData = RequestData::fromRequest($request);
        $response = new Response(json_encode(['data' => $data]), Response::HTTP_OK);
        $responseData = ResponseData::fromResponse($response);

        /* EXECUTE */
        $result = $this->responsePluckStrategy->handle(
            requestData: $requestData,
            responseData: $responseData,
            options: [
                'position' => 'data.*.id',
                'order' => OrderType::DESC->value,
                'limit' => $limit,
            ]
        );

        /* ASSERT */
        $this->assertCount($limit, $result);
        $this->assertEquals([$thirdId, $secondId], $result);
        $this->assertNotContains($firstId, $result);
    }

    #[Test]
    public function it_throws_missing_strategy_option_exception_when_route_key_missing_in_options(): void
    {
        /* SETUP */
        $request = Request::create(
            uri: "/sample_endpoint",
            method: Request::METHOD_GET,
        );
        $requestData = RequestData::fromRequest($request);
        $responseData = new ResponseData(Response::HTTP_OK);
        $this->expectException(MissingStrategyOptionException::class);

        /* EXECUTE */
        $this->responsePluckStrategy->handle(
            requestData: $requestData,
            responseData: $responseData,
            options: []
        );
    }

    #[Test]
    public function it_handles_null_values_in_data_array_so_they_appear_at_the_end(): void
    {
        /* SETUP */
        $firstId = 32;
        $secondId = 33;
        $data = [
            ['id' => null],
            ['id' => $secondId],
            ['id' => $firstId],
        ];
        $request = Request::create(
            uri: "/sample_endpoint",
            method: Request::METHOD_GET
        );
        $requestData = RequestData::fromRequest($request);
        $response = new Response(json_encode(['data' => $data]), Response::HTTP_OK);
        $responseData = ResponseData::fromResponse($response);
    
        /* EXECUTE */
        $result = $this->responsePluckStrategy->handle(
            requestData: $requestData,
            responseData: $responseData,
            options: [
                'position' => 'data.*.id',
                'order' => OrderType::ASC->value,
            ]
        );
    
        /* ASSERT */
        $this->assertEquals([$firstId, $secondId, null], $result);
    }
}