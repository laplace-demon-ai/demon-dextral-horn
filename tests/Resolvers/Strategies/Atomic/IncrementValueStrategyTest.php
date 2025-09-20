<?php

declare(strict_types=1);

namespace Tests\Resolvers\Strategies\Atomic;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Resolvers\Strategies\Atomic\IncrementValueStrategy;

#[CoversClass(IncrementValueStrategy::class)]
final class IncrementValueStrategyTest extends TestCase
{
    private IncrementValueStrategy $strategy;
    private ResponseData $responseData;

    public function setUp(): void
    {
        parent::setUp();

        $this->strategy = app(IncrementValueStrategy::class);
        $response = new Response(json_encode(['data' => 'ok']), Response::HTTP_OK);
        $this->responseData = ResponseData::fromResponse($response);
    }

    #[Test]
    public function it_increments_existing_value(): void
    {
        /* EXECUTE */
        $result = $this->strategy->handle(null, $this->responseData, ['increment' => 2], 5);

        /* ASSERT */
        $this->assertEquals(7, $result);
    }

    #[Test]
    public function it_uses_default_when_value_is_null(): void
    {
        /* EXECUTE */
        $result = $this->strategy->handle(null, $this->responseData, ['increment' => 3, 'default' => 10]);

        /* ASSERT */
        $this->assertEquals(13, $result);
    }

    #[Test]
    public function it_defaults_to_one_for_increment_and_default(): void
    {
        /* EXECUTE */
        $result = $this->strategy->handle(null, $this->responseData);

        /* ASSERT */
        $this->assertEquals(2, $result); // default(1) + increment(1)
    }

    #[Test]
    public function it_accepts_string_increment_and_casts_to_int(): void
    {
        /* EXECUTE */
        $result = $this->strategy->handle(null, $this->responseData, ['increment' => '5'], 2);

        /* ASSERT */
        $this->assertEquals(7, $result);
    }

    #[Test]
    public function it_accepts_string_value_and_casts_to_int(): void
    {
        /* EXECUTE */
        $result = $this->strategy->handle(null, $this->responseData, ['increment' => 2], '3');

        /* ASSERT */
        $this->assertEquals(5, $result);
    }

    #[Test]
    public function it_casts_non_numeric_value_to_zero(): void
    {
        /* EXECUTE */
        $result = $this->strategy->handle(null, $this->responseData, ['increment' => 4], 'abc');

        /* ASSERT */
        $this->assertEquals(4, $result);
    }
}
