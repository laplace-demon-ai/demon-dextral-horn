<?php

declare(strict_types=1);

namespace Tests\Resolvers\Strategies\Composite;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use DemonDextralHorn\Resolvers\Strategies\Composite\IncrementQueryParamStrategy;
use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Exceptions\MissingStrategyOptionException;

#[CoversClass(IncrementQueryParamStrategy::class)]
final class IncrementQueryParamStrategyTest extends TestCase
{
    private IncrementQueryParamStrategy $incrementQueryParamStrategy;
    private ResponseData $responseData;

    public function setUp(): void
    {
        parent::setUp();

        $response = new Response('ok', Response::HTTP_OK);
        $this->responseData = ResponseData::fromResponse($response);
        $this->incrementQueryParamStrategy = app(IncrementQueryParamStrategy::class);
    }

    #[Test]
    public function it_tests_increment_strategy_when_query_key_is_present(): void
    {
        /* SETUP */
        $initialValue = 5;
        $request = Request::create(
            uri: "/sample_endpoint?query_key=" . $initialValue,
            method: Request::METHOD_POST,
        );
        $requestData = RequestData::fromRequest($request);
        $increment = 2;

        /* EXECUTE */
        $result = $this->incrementQueryParamStrategy->handle(
            requestData: $requestData,
            responseData: $this->responseData,
            options: ['source_key' => 'query_key', 'increment' => $increment]
        );

        /* ASSERT */
        $this->assertEquals($initialValue + $increment, $result);

    }

    #[Test]
    public function it_tests_increment_strategy_when_query_key_is_NOT_present(): void
    {
        /* SETUP */
        $defaultValue = 1; // Default value when query key is not present
        $request = Request::create(
            uri: "/sample_endpoint",
            method: Request::METHOD_POST,
        );
        $requestData = RequestData::fromRequest($request);
        $increment = 2;

        /* EXECUTE */
        $result = $this->incrementQueryParamStrategy->handle(
            requestData: $requestData,
            responseData: $this->responseData,
            options: ['source_key' => 'query_key', 'increment' => $increment]
        );

        /* ASSERT */
        $this->assertEquals($defaultValue + $increment, $result);
    }

    #[Test]
    public function it_defaults_to_increment_by_one_when_increment_not_provided(): void
    {
        /* SETUP */
        $defaultValue = 1; // Default value when increment is not provided
        $initialValue = 5;
        $request = Request::create("/sample_endpoint?query_key=" . $initialValue, Request::METHOD_POST);
        $requestData = RequestData::fromRequest($request);

        /* EXECUTE */
        $result = $this->incrementQueryParamStrategy->handle(
            requestData: $requestData,
            responseData: $this->responseData,
            options: ['source_key' => 'query_key']
        );

        /* ASSERT */
        $this->assertEquals($defaultValue + $initialValue, $result);
    }

    #[Test]
    public function it_throws_missing_strategy_option_exception_when_query_key_missing(): void
    {
        /* SETUP */
        $defaultValue = 1; // Default value when query key is not present
        $request = Request::create(
            uri: "/sample_endpoint",
            method: Request::METHOD_POST,
        );
        $requestData = RequestData::fromRequest($request);
        $increment = 2;
        $this->expectException(MissingStrategyOptionException::class);

        /* EXECUTE */
        $result = $this->incrementQueryParamStrategy->handle(
            requestData: $requestData,
            responseData: $this->responseData,
            options: ['increment' => $increment]
        );

        /* ASSERT */
        $this->assertEquals($defaultValue + $increment, $result);
    }

    #[Test]
    public function it_tests_when_increment_is_string_it_works_as_integer(): void
    {
        /* SETUP */
        $defaultValue = 1; // Default value when query key is not present
        $request = Request::create(
            uri: "/sample_endpoint",
            method: Request::METHOD_POST,
        );
        $requestData = RequestData::fromRequest($request);
        $increment = '2'; // string increment

        /* EXECUTE */
        $result = $this->incrementQueryParamStrategy->handle(
            requestData: $requestData,
            responseData: $this->responseData,
            options: ['source_key' => 'query_key', 'increment' => $increment]
        );

        /* ASSERT */
        $this->assertEquals($defaultValue + $increment, $result);
    }

    #[Test]
    public function it_defaults_to_one_when_request_data_is_null(): void
    {
        /* SETUP */
        $defaultValue = 1; // Default value when query key is not present
        $increment = 2;

        /* EXECUTE */
        $result = $this->incrementQueryParamStrategy->handle(
            requestData: null,
            responseData: $this->responseData,
            options: ['source_key' => 'query_key', 'increment' => $increment]
        );

        /* ASSERT */
        $this->assertEquals($defaultValue + $increment, $result);
    }

    #[Test]
    public function it_casts_non_numeric_query_value_to_zero(): void
    {
        /* SETUP */
        $increment = 2;
        $request = Request::create("/sample_endpoint?query_key=foo", Request::METHOD_POST);
        $requestData = RequestData::fromRequest($request);

        /* EXECUTE */
        $result = $this->incrementQueryParamStrategy->handle(
            requestData: $requestData,
            responseData: $this->responseData,
            options: ['source_key' => 'query_key', 'increment' => $increment]
        );

        /* ASSERT */
        $this->assertEquals($increment, $result);
    }

    #[Test]
    public function it_uses_custom_default_when_query_key_is_missing(): void
    {
        /* SETUP */
        $customDefault = 10;
        $increment = 3;
        $request = Request::create("/sample_endpoint", Request::METHOD_POST);
        $requestData = RequestData::fromRequest($request);

        /* EXECUTE */
        $result = $this->incrementQueryParamStrategy->handle(
            requestData: $requestData,
            responseData: $this->responseData,
            options: ['source_key' => 'query_key', 'increment' => $increment, 'default' => $customDefault]
        );

        /* ASSERT */
        $this->assertEquals($customDefault + $increment, $result);
    }
}