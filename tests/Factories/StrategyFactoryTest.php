<?php

declare(strict_types=1);

namespace Tests\Factories;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use InvalidArgumentException;
use DemonDextralHorn\Factories\StrategyFactory;
use DemonDextralHorn\Resolvers\Contracts\StrategyInterface;

#[CoversClass(StrategyFactory::class)]
final class StrategyFactoryTest extends TestCase
{
    private StrategyFactory $strategyFactory;

    public function setUp(): void
    {
        parent::setUp();

        $this->strategyFactory = app(StrategyFactory::class);
    }

    #[Test]
    public function it_successfully_instantiates_a_strategy_by_given_class_name(): void
    {
        /* SETUP */
        $class = StrategySample::class;

        /* EXECUTE */
        $strategy = $this->strategyFactory->make($class);

        /* ASSERT */
        $this->assertInstanceOf(StrategyInterface::class, $strategy);
        $this->assertInstanceOf(StrategySample::class, $strategy);
        $this->assertEquals('sample_response', $strategy->handle(null, null));
    }

    #[Test]
    public function it_throws_invalid_argument_exception_when_empty_string_is_provided(): void
    {
        /* SETUP */
        $class = '';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Strategy class not provided');

        /* EXECUTE */
        $this->strategyFactory->make($class);
    }

    #[Test]
    public function it_throws_invalid_argument_exception_when_non_existent_class_is_provided(): void
    {
        /* SETUP */
        $class = 'NonExistentClass';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Strategy class does not exist: '. $class);

        /* EXECUTE */
        $this->strategyFactory->make($class);
    }

    #[Test]
    public function it_throws_invalid_argument_exception_when_class_exists_but_not_subclass_of_strategy_interface(): void
    {
        /* SETUP */
        $class = StrategySampleWithoutInterface::class;
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Class {$class} must implement StrategyInterface");

        /* EXECUTE */
        $this->strategyFactory->make($class);
    }
}