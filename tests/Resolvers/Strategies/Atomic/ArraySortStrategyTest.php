<?php

declare(strict_types=1);

namespace Tests\Resolvers\Strategies\Atomic;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;
use DemonDextralHorn\Resolvers\Strategies\Atomic\ArraySortStrategy;
use DemonDextralHorn\Enums\OrderType;
use DemonDextralHorn\Exceptions\MissingStrategyOptionException;

#[CoversClass(ArraySortStrategy::class)]
final class ArraySortStrategyTest extends TestCase
{
    private ArraySortStrategy $strategy;

    public function setUp(): void
    {
        parent::setUp();

        $this->strategy = app(ArraySortStrategy::class);
    }

    #[Test]
    public function it_sorts_flat_array_in_ascending_order(): void
    {
        /* SETUP */
        $options = [
            'order' => OrderType::ASC->value,
        ];
        $value = [34, 3, 134];
        $expected = [3, 34, 134];

        /* EXECUTE */
        $result = $this->strategy->handle(options: $options, value: $value);

        /* ASSERT */
        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function it_sorts_flat_array_in_descending_order(): void
    {
        /* SETUP */
        $options = [
            'order' => OrderType::DESC->value,
        ];
        $value = [34, 3, 134];
        $expected = [134, 34, 3];

        /* EXECUTE */
        $result = $this->strategy->handle(options: $options, value: $value);

        /* ASSERT */
        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function it_returns_the_same_value_if_not_an_array(): void
    {
        /* SETUP */
        $options = [
            'order' => OrderType::ASC->value,
        ];
        $value = 'not-an-array';

        /* EXECUTE */
        $result = $this->strategy->handle(options: $options, value: $value);

        /* ASSERT */
        $this->assertEquals($value, $result);
    }

    #[Test]
    public function it_returns_the_same_array_if_order_is_empty(): void
    {
        /* SETUP */
        $options = []; // no 'order' key
        $value = [3, 2, 1];
    
        /* EXECUTE */
        $result = $this->strategy->handle(options: $options, value: $value);
    
        /* ASSERT */
        $this->assertEquals($value, $result);
    }

    #[Test]
    public function it_places_null_values_at_the_end_when_sorting_ascending(): void
    {
        /* SETUP */
        $options = [
            'order' => OrderType::ASC->value,
        ];
        $value = [5, null, 2, null, 9];
        $expected = [2, 5, 9, null, null];
    
        /* EXECUTE */
        $result = $this->strategy->handle(options: $options, value: $value);
    
        /* ASSERT */
        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function it_places_null_values_at_the_end_when_sorting_descending(): void
    {
        /* SETUP */
        $options = [
            'order' => OrderType::DESC->value,
        ];
        $value = [5, null, 2, null, 9];
        $expected = [9, 5, 2, null, null];
    
        /* EXECUTE */
        $result = $this->strategy->handle(options: $options, value: $value);
    
        /* ASSERT */
        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function it_throws_exception_for_invalid_order(): void
    {
        /* SETUP */
        $options = [
            'order' => 'invalid-order',
        ];
        $value = [1, 2, 3];
        $this->expectException(MissingStrategyOptionException::class);
    
        /* EXECUTE */
        $this->strategy->handle(options: $options, value: $value);
    }

    #[Test]
    public function it_sorts_array_with_mixed_types(): void
    {
        /* SETUP */
        $options = [
            'order' => OrderType::ASC->value,
        ];
        $value = [3, '2', 1];
        $expected = [1, '2', 3];
    
        /* EXECUTE */
        $result = $this->strategy->handle(options: $options, value: $value);
    
        /* ASSERT */
        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function it_returns_empty_array_if_given_empty_array(): void
    {
        /* SETUP */
        $options = [
            'order' => OrderType::ASC->value,
        ];
        $value = [];
        $expected = [];
    
        /* EXECUTE */
        $result = $this->strategy->handle(options: $options, value: $value);
    
        /* ASSERT */
        $this->assertEquals($expected, $result);
    }
}