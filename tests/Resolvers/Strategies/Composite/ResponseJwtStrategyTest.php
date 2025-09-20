<?php

declare(strict_types=1);

namespace Tests\Resolvers\Strategies\Composite;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Resolvers\Strategies\Composite\ResponseJwtStrategy;
use DemonDextralHorn\Exceptions\MissingStrategyOptionException;

#[CoversClass(ResponseJwtStrategy::class)]
final class ResponseJwtStrategyTest extends TestCase
{
    private ResponseJwtStrategy $responseJwtStrategy;
    private ResponseData $responseData;
    private string $jwtToken = 'jwt-token';

    public function setUp(): void
    {
        parent::setUp();

        $data = ['token' => $this->jwtToken];
        $response = new Response(json_encode(['data' => $data]), Response::HTTP_OK);
        $this->responseData = ResponseData::fromResponse($response);
        $this->responseJwtStrategy = app(ResponseJwtStrategy::class);
    }

    #[Test]
    public function it_extracts_jwt_and_prefixes_with_bearer(): void
    {
        /* EXECUTE */
        $result = $this->responseJwtStrategy->handle(
            responseData: $this->responseData,
            options: ['position' => 'data.token'],
        );

        /* ASSERT */
        $this->assertSame('Bearer ' . $this->jwtToken, $result);
    }

    #[Test]
    public function it_throws_exception_when_position_not_provided(): void
    {
        /* SETUP */
        $this->expectException(MissingStrategyOptionException::class);

        /* EXECUTE */
        $result = $this->responseJwtStrategy->handle(
            responseData: $this->responseData,
            options: [],
        );

        /* ASSERT */
        $this->assertSame('Bearer ', $result);
    }

    #[Test]
    public function it_returns_null_when_position_not_found(): void
    {
        /* EXECUTE */
        $result = $this->responseJwtStrategy->handle(
            responseData: $this->responseData,
            options: ['position' => 'data.missing'],
        );

        /* ASSERT */
        $this->assertNull($result);
    }
}