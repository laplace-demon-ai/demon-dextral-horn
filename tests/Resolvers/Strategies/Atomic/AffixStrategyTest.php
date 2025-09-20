<?php

declare(strict_types=1);

namespace Tests\Resolvers\Strategies\Atomic;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;
use DemonDextralHorn\Resolvers\Strategies\Atomic\AffixStrategy;

#[CoversClass(AffixStrategy::class)]
final class AffixStrategyTest extends TestCase
{
    private AffixStrategy $affixStrategy;

    public function setUp(): void
    {
        parent::setUp();

        $this->affixStrategy = app(AffixStrategy::class);
    }

    #[Test]
    public function it_returns_value_as_is_when_no_prefix_or_suffix_provided(): void
    {
        /* SETUP */
        $value = 'value';
        $options = [];

        /* EXECUTE */
        $result = $this->affixStrategy->handle(
            options: $options,
            value: $value
        );

        /* ASSERT */
        $this->assertSame('value', $result);
    }

    #[Test]
    public function it_adds_prefix_when_only_prefix_is_provided(): void
    {
        /* SETUP */
        $value = 'value';
        $options = ['prefix' => 'pre-'];

        /* EXECUTE */
        $result = $this->affixStrategy->handle(
            options: $options,
            value: $value
        );

        /* ASSERT */
        $this->assertSame('pre-value', $result);
    }

    #[Test]
    public function it_adds_suffix_when_only_suffix_is_provided(): void
    {
        /* SETUP */
        $value = 'value';
        $options = ['suffix' => '-suf'];

        /* EXECUTE */
        $result = $this->affixStrategy->handle(
            options: $options,
            value: $value
        );

        /* ASSERT */
        $this->assertSame('value-suf', $result);
    }

    #[Test]
    public function it_adds_prefix_and_suffix_when_both_are_provided(): void
    {
        /* SETUP */
        $value = 'value';
        $options = ['prefix' => 'pre-', 'suffix' => '-suf'];

        /* EXECUTE */
        $result = $this->affixStrategy->handle(
            options: $options,
            value: $value
        );

        /* ASSERT */
        $this->assertSame('pre-value-suf', $result);
    }

    #[Test]
    public function it_returns_non_string_values_as_is(): void
    {
        /* SETUP */
        $value = 123;
        $options = ['prefix' => 'pre-', 'suffix' => '-suf'];

        /* EXECUTE */
        $result = $this->affixStrategy->handle(
            options: $options,
            value: $value
        );

        /* ASSERT */
        $this->assertSame(123, $result);
    }
}