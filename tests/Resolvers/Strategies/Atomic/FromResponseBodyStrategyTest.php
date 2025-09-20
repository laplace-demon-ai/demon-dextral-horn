<?php

declare(strict_types=1);

namespace Tests\Resolvers\Strategies\Atomic;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use DemonDextralHorn\Resolvers\Strategies\Atomic\FromResponseBodyStrategy;
use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Exceptions\MissingStrategyOptionException;

#[CoversClass(FromResponseBodyStrategy::class)]
final class FromResponseBodyStrategyTest extends TestCase
{
    private FromResponseBodyStrategy $fromResponseBodyStrategy;

    public function setUp(): void
    {
        parent::setUp();

        $this->fromResponseBodyStrategy = app(FromResponseBodyStrategy::class);
    }

    #[Test]
    public function it_tests_from_response_body_strategy_when_position_is_present(): void
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
        $result = $this->fromResponseBodyStrategy->handle(
            requestData: $requestData,
            responseData: $responseData,
            options: [
                'position' => 'data.id'
            ]
        );

        /* ASSERT */
        $this->assertEquals($id, $result);
    }

    #[Test]
    public function it_tests_from_response_body_strategy_when_position_is_present_and_response_has_a_collection(): void
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
        $result = $this->fromResponseBodyStrategy->handle(
            requestData: $requestData,
            responseData: $responseData,
            options: [
                'position' => 'data.0'
            ]
        );

        /* ASSERT */
        $this->assertEquals($ids[0], $result);
    }

    #[Test]
    public function it_throws_missing_strategy_option_exception_when_position_is_missing_in_options(): void
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
        $this->fromResponseBodyStrategy->handle(
            requestData: $requestData,
            responseData: $responseData,
            options: []
        );
    }

    #[Test]
    public function it_tests_from_response_body_strategy_when_position_is_data_wildcard(): void
    {
        /* SETUP */
        $ids = [32, 33, 34];
        $data = [
            'data' => [
                ['id' => 32, 'name' => 'Alice'],
                ['id' => 33, 'name' => 'Bob'],
                ['id' => 34, 'name' => 'Charlie'],
            ]
        ];
        $request = Request::create(
            uri: "/sample_endpoint",
            method: Request::METHOD_GET,
        );
        $requestData = RequestData::fromRequest($request);
        $response = new Response(json_encode($data), Response::HTTP_OK);
        $responseData = ResponseData::fromResponse($response);

        /* EXECUTE */
        $result = $this->fromResponseBodyStrategy->handle(
            requestData: $requestData,
            responseData: $responseData,
            options: [
                'position' => 'data.*.id'
            ]
        );

        /* ASSERT */
        $this->assertEquals($ids, $result);
    }
}