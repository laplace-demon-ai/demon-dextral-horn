<?php

declare(strict_types=1);

namespace Tests\Resolvers\Strategies\Atomic;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;
use DemonDextralHorn\Resolvers\Strategies\Atomic\ArrayLimitStrategy;
use DemonDextralHorn\Exceptions\MissingStrategyOptionException;

#[CoversClass(ArrayLimitStrategy::class)]
final class ArrayLimitStrategyTest extends TestCase
{
    private ArrayLimitStrategy $arrayLimitStrategy;

    public function setUp(): void
    {
        parent::setUp();

        $this->arrayLimitStrategy = app(ArrayLimitStrategy::class);
    }

    #[Test]
    public function it_limits_array_to_given_size(): void
    {
        /* SETUP */
        $value = [1, 2, 3, 4, 5];
        $options = ['limit' => 3];

        /* EXECUTE */
        $result = $this->arrayLimitStrategy->handle(
            options: $options,
            value: $value
        );

        /* ASSERT */
        $this->assertSame([1, 2, 3], $result);
    }

    #[Test]
    public function it_returns_empty_array_when_limit_is_zero_or_less(): void
    {
        /* SETUP */
        $value = [1, 2, 3];

        /* EXECUTE & ASSERT */
        $this->expectException(MissingStrategyOptionException::class);

        $this->arrayLimitStrategy->handle(
            options: ['limit' => 0],
            value: $value
        );
    }

    #[Test]
    public function it_throws_exception_when_limit_is_missing(): void
    {
        /* SETUP */
        $value = [1, 2, 3];

        /* EXECUTE & ASSERT */
        $this->expectException(MissingStrategyOptionException::class);

        $this->arrayLimitStrategy->handle(
            options: [],
            value: $value
        );
    }

    #[Test]
    public function it_returns_same_value_if_not_array(): void
    {
        /* SETUP */
        $value = 'not-an-array';
        $options = ['limit' => 2];

        /* EXECUTE */
        $result = $this->arrayLimitStrategy->handle(
            options: $options,
            value: $value
        );

        /* ASSERT */
        $this->assertSame('not-an-array', $result);
    }
}