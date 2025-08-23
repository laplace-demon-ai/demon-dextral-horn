<?php

declare(strict_types=1);

namespace Tests\Resolvers\Strategies\Transform;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Http\Request;
use DemonDextralHorn\Resolvers\Strategies\Transform\IncrementStrategy;
use DemonDextralHorn\Data\RequestData;
use DemonDextralHorn\Exceptions\MissingStrategyOptionException;

/**
 * Test for IncrementStrategy.
 * 
 * @class IncrementStrategyTest
 */
final class IncrementStrategyTest extends TestCase
{
    private IncrementStrategy $incrementStrategy;

    public function setUp(): void
    {
        parent::setUp();

        $this->incrementStrategy = app(IncrementStrategy::class);
    }

    #[Test]
    public function it_tests_increment_strategy_when_query_key_is_present(): void
    {
        /* SETUP */
        $initialValue = 5;
        $request = Request::create(
            uri: "/sample_endpoint?query_key=" . $initialValue,
            method: 'POST',
        );
        $requestData = RequestData::fromRequest($request);
        $increment = 2;

        /* EXECUTE */
        $result = $this->incrementStrategy->handle(
            requestData: $requestData,
            options: ['key' => 'query_key', 'increment' => $increment]
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
            method: 'POST',
        );
        $requestData = RequestData::fromRequest($request);
        $increment = 2;

        /* EXECUTE */
        $result = $this->incrementStrategy->handle(
            requestData: $requestData,
            options: ['key' => 'query_key', 'increment' => $increment]
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
        $request = Request::create("/sample_endpoint?query_key=" . $initialValue, 'POST');
        $requestData = RequestData::fromRequest($request);

        /* EXECUTE */
        $result = $this->incrementStrategy->handle(
            requestData: $requestData,
            options: ['key' => 'query_key']
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
            method: 'POST',
        );
        $requestData = RequestData::fromRequest($request);
        $increment = 2;
        $this->expectException(MissingStrategyOptionException::class);

        /* EXECUTE */
        $result = $this->incrementStrategy->handle(
            requestData: $requestData,
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
            method: 'POST',
        );
        $requestData = RequestData::fromRequest($request);
        $increment = '2'; // string increment

        /* EXECUTE */
        $result = $this->incrementStrategy->handle(
            requestData: $requestData,
            options: ['key' => 'query_key', 'increment' => $increment]
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
        $result = $this->incrementStrategy->handle(
            requestData: null,
            options: ['key' => 'query_key', 'increment' => $increment]
        );

        /* ASSERT */
        $this->assertEquals($defaultValue + $increment, $result);
    }

    #[Test]
    public function it_casts_non_numeric_query_value_to_zero(): void
    {
        /* SETUP */
        $increment = 2;
        $request = Request::create("/sample_endpoint?query_key=foo", 'POST');
        $requestData = RequestData::fromRequest($request);

        /* EXECUTE */
        $result = $this->incrementStrategy->handle(
            requestData: $requestData,
            options: ['key' => 'query_key', 'increment' => $increment]
        );

        /* ASSERT */
        $this->assertEquals($increment, $result);
    }
}