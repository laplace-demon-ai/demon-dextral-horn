<?php

declare(strict_types=1);

namespace Tests\Resolvers\Strategies\Atomic;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Resolvers\Strategies\Atomic\FromRequestQueryStrategy;
use DemonDextralHorn\Exceptions\MissingStrategyOptionException;

#[CoversClass(FromRequestQueryStrategy::class)]
final class FromRequestQueryStrategyTest extends TestCase
{
    private FromRequestQueryStrategy $strategy;
    private ResponseData $responseData;

    public function setUp(): void
    {
        parent::setUp();
        
        $this->strategy = app(FromRequestQueryStrategy::class);
        $response = new Response(json_encode(['data' => 'ok']), Response::HTTP_OK);
        $this->responseData = ResponseData::fromResponse($response);
    }

    #[Test]
    public function it_returns_value_when_query_param_exists(): void
    {
        /* SETUP */
        $request = Request::create("/endpoint?sample_query_param=sample_value", Request::METHOD_GET);
        $requestData = RequestData::fromRequest($request);

        /* EXECUTE */
        $result = $this->strategy->handle($requestData, $this->responseData, ['source_key' => 'sample_query_param']);

        /* ASSERT */
        $this->assertEquals('sample_value', $result);
    }

    #[Test]
    public function it_returns_null_when_query_param_does_not_exist(): void
    {
        /* SETUP */
        $request = Request::create("/endpoint", Request::METHOD_GET);
        $requestData = RequestData::fromRequest($request);

        /* EXECUTE */
        $result = $this->strategy->handle($requestData, $this->responseData, ['source_key' => 'sample_query_param']);

        /* ASSERT */
        $this->assertNull($result);
    }

    #[Test]
    public function it_throws_exception_when_source_key_missing(): void
    {
        /* SETUP */
        $this->expectException(MissingStrategyOptionException::class);
        $request = Request::create("/endpoint?sample_query_param=sample_value", Request::METHOD_GET);
        $requestData = RequestData::fromRequest($request);

        /* EXECUTE */
        $this->strategy->handle($requestData, $this->responseData, []);
    }

    #[Test]
    public function it_returns_null_when_request_data_is_null(): void
    {
        /* EXECUTE */
        $result = $this->strategy->handle(null, $this->responseData, ['source_key' => 'sample_query_param']);

        /* ASSERT */
        $this->assertNull($result);
    }
}
