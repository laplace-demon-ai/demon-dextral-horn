<?php

declare(strict_types=1);

namespace Tests\Resolvers\Strategies\Composite;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Exceptions\MissingStrategyOptionException;
use DemonDextralHorn\Resolvers\Strategies\Composite\ForwardQueryParamStrategy;

#[CoversClass(ForwardQueryParamStrategy::class)]
final class ForwardQueryParamStrategyTest extends TestCase
{
    private ForwardQueryParamStrategy $forwardQueryParamStrategy;
    private ResponseData $responseData;

    public function setUp(): void
    {
        parent::setUp();

        $this->responseData = new ResponseData(
            status: Response::HTTP_OK,
            content: 'ok'
        );
        $this->forwardQueryParamStrategy = app(ForwardQueryParamStrategy::class);
    }

    #[Test]
    public function it_tests_forward_query_param_strategy_when_query_key_is_present(): void
    {
        /* SETUP */
        $value = 5;
        $request = Request::create(
            uri: "/sample_endpoint?query_key=" . $value,
            method: Request::METHOD_GET,
        );
        $requestData = RequestData::fromRequest($request);

        /* EXECUTE */
        $result = $this->forwardQueryParamStrategy->handle(
            requestData: $requestData,
            options: ['source_key' => 'query_key']
        );

        /* ASSERT */
        $this->assertEquals($value, $result);

    }

    #[Test]
    public function it_tests_forward_query_param_strategy_when_query_key_is_NOT_present(): void
    {
        /* SETUP */
        $request = Request::create(
            uri: "/sample_endpoint",
            method: Request::METHOD_GET,
        );
        $requestData = RequestData::fromRequest($request);
        $this->expectException(MissingStrategyOptionException::class);

        /* EXECUTE */
        $this->forwardQueryParamStrategy->handle(
            requestData: $requestData,
            responseData: $this->responseData,
            options: ['source_key' => 'query_key']
        );
    }

    #[Test]
    public function it_throws_missing_strategy_option_exception_when_query_key_missing_in_options(): void
    {
        /* SETUP */
        $request = Request::create(
            uri: "/sample_endpoint?query_key=12",
            method: Request::METHOD_GET,
        );
        $requestData = RequestData::fromRequest($request);
        $this->expectException(MissingStrategyOptionException::class);

        /* EXECUTE */
        $this->forwardQueryParamStrategy->handle(
            requestData: $requestData,
            responseData: $this->responseData,
            options: []
        );
    }

    #[Test]
    public function it_forwards_string_query_param(): void
    {
        /* SETUP */
        $stringValue = 'some_value';
        $request = Request::create("/sample_endpoint?query_key={$stringValue}", Request::METHOD_GET);
        $requestData = RequestData::fromRequest($request);

        /* EXECUTE */
        $result = $this->forwardQueryParamStrategy->handle(
            requestData: $requestData,
            responseData: $this->responseData,
            options: ['source_key' => 'query_key']
        );

        /* ASSERT */
        $this->assertEquals($stringValue, $result);
    }

    #[Test]
    public function it_tests_forward_query_param_strategy_when_query_key_is_array(): void
    {
        /* SETUP */
        $firstItem = 1;
        $secondItem = 2;
        $thirdItem = 3;
        $request = Request::create(
            uri: "/sample_endpoint?items[]={$firstItem}&items[]={$secondItem}&items[]={$thirdItem}",
            method: Request::METHOD_GET,
        );
        $requestData = RequestData::fromRequest($request);

        /* EXECUTE */
        $result = $this->forwardQueryParamStrategy->handle(
            requestData: $requestData,
            responseData: $this->responseData,
            options: ['source_key' => 'items']
        );

        /* ASSERT */
        $this->assertEquals([$firstItem, $secondItem, $thirdItem], $result);
    }
}