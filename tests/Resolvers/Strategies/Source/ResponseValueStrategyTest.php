<?php

declare(strict_types=1);

namespace Tests\Resolvers\Strategies\Transform;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use DemonDextralHorn\Resolvers\Strategies\Source\ResponseValueStrategy;
use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Exceptions\MissingStrategyOptionException;

#[CoversClass(ResponseValueStrategy::class)]
final class ResponseValueStrategyTest extends TestCase
{
    private ResponseValueStrategy $responseValueStrategy;

    public function setUp(): void
    {
        parent::setUp();

        $this->responseValueStrategy = app(ResponseValueStrategy::class);
    }

    #[Test]
    public function it_tests_response_value_strategy_when_route_key_is_present(): void
    {
        /* SETUP */
        $id = 32;
        $data = ['id' => $id];
        $request = Request::create(
            uri: "/sample_endpoint",
            method: Request::METHOD_GET,
        );
        $requestData = RequestData::fromRequest($request);
        $response = new Response(json_encode(['data' => $data]), Response::HTTP_OK);
        $responseData = ResponseData::fromResponse($response);

        /* EXECUTE */
        $result = $this->responseValueStrategy->handle(
            requestData: $requestData,
            responseData: $responseData,
            options: [
                'key' => 'route_key',
                'position' => 'data.id'
            ]
        );

        /* ASSERT */
        $this->assertEquals($id, $result);
    }

    #[Test]
    public function it_tests_response_value_strategy_when_route_key_and_position_are_present_and_response_has_a_collection(): void
    {
        /* SETUP */
        $ids = [32, 33, 34];
        $data = ['data' => $ids];
        $request = Request::create(
            uri: "/sample_endpoint",
            method: Request::METHOD_GET,
        );
        $requestData = RequestData::fromRequest($request);
        $response = new Response(json_encode($data), Response::HTTP_OK);
        $responseData = ResponseData::fromResponse($response);

        /* EXECUTE */
        $result = $this->responseValueStrategy->handle(
            requestData: $requestData,
            responseData: $responseData,
            options: [
                'key' => 'route_key',
                'position' => 'data.0'
            ]
        );

        /* ASSERT */
        $this->assertEquals($ids[0], $result);
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
        $this->responseValueStrategy->handle(
            requestData: $requestData,
            responseData: $responseData,
            options: []
        );
    }
}