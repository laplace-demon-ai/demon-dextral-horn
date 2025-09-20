<?php

declare(strict_types=1);

namespace Tests\Resolvers\Strategies\Atomic;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;
use DemonDextralHorn\Resolvers\Strategies\Atomic\FromResponseHeaderStrategy;
use DemonDextralHorn\Data\ResponseData;
use DemonDextralHorn\Data\HeadersData;
use DemonDextralHorn\Exceptions\MissingStrategyOptionException;

#[CoversClass(FromResponseHeaderStrategy::class)]
final class FromResponseHeaderStrategyTest extends TestCase
{
    private FromResponseHeaderStrategy $fromResponseHeaderStrategy;
    private string $sessionCookieName;

    public function setUp(): void
    {
        parent::setUp();

        $this->fromResponseHeaderStrategy = app(FromResponseHeaderStrategy::class);
        $this->sessionCookieName = config('demon-dextral-horn.defaults.session_cookie_name');
    }

    #[Test]
    public function it_returns_the_header_value_for_set_cookie(): void
    {
        /* SETUP */
        $headers = new HeadersData(
            setCookie: [$this->sessionCookieName . '=xyz789; Path=/; HttpOnly']
        );
        $responseData = new ResponseData(status: 200, headers: $headers);
        $options = [
            'header_name' => 'setCookie',
        ];

        /* EXECUTE */
        $result = $this->fromResponseHeaderStrategy->handle(responseData: $responseData, options: $options);

        /* ASSERT */
        $this->assertSame([$this->sessionCookieName . '=xyz789; Path=/; HttpOnly'], $result);
    }

    #[Test]
    public function it_returns_the_header_value_for_authorization(): void
    {
        /* SETUP */
        $headers = new HeadersData(
            authorization: 'Bearer abc123'
        );
        $responseData = new ResponseData(status: 200, headers: $headers);
        $options = [
            'header_name' => 'authorization',
        ];

        /* EXECUTE */
        $result = $this->fromResponseHeaderStrategy->handle(responseData: $responseData, options: $options);

        /* ASSERT */
        $this->assertSame('Bearer abc123', $result);
    }

    #[Test]
    public function it_throws_missing_strategy_option_exception_when_header_name_is_missing_in_options(): void
    {
        /* SETUP */
        $headers = new HeadersData(
            authorization: 'Bearer abc123'
        );
        $responseData = new ResponseData(status: 200, headers: $headers);
        $options = [
            'invalid_key' => 'some_value',
        ];
        $this->expectException(MissingStrategyOptionException::class);

        /* EXECUTE */
        $result = $this->fromResponseHeaderStrategy->handle(responseData: $responseData, options: $options);
    }

    #[Test]
    public function it_returns_the_header_value_for_accept_headers(): void
    {
        /* SETUP */
        $accept = 'application/json';
        $acceptLanguage = 'en-US';
        $headers = new HeadersData(
            accept: $accept,
            acceptLanguage: $acceptLanguage
        );
        $responseData = new ResponseData(status: 200, headers: $headers);

        /* EXECUTE & ASSERT */
        $acceptResult = $this->fromResponseHeaderStrategy->handle(
            responseData: $responseData,
            options: ['header_name' => 'accept'],
        );
        $acceptLangResult = $this->fromResponseHeaderStrategy->handle(
            responseData: $responseData,
            options: ['header_name' => 'acceptLanguage'],
        );

        $this->assertSame($accept, $acceptResult);
        $this->assertSame($acceptLanguage, $acceptLangResult);
    }

    #[Test]
    public function it_returns_null_for_nonexistent_header(): void
    {
        /* SETUP */
        $headers = new HeadersData();
        $responseData = new ResponseData(status: 200, headers: $headers);

        $options = [
            'header_name' => 'nonexistentHeader',
        ];

        /* EXECUTE */
        $result = $this->fromResponseHeaderStrategy->handle(responseData: $responseData, options: $options);

        /* ASSERT */
        $this->assertNull($result);
    }

    #[Test]
    public function it_returns_null_if_response_data_is_null(): void
    {
        /* SETUP */
        $options = [
            'header_name' => 'setCookie',
        ];

        /* EXECUTE */
        $result = $this->fromResponseHeaderStrategy->handle(options: $options);

        /* ASSERT */
        $this->assertNull($result);
    }
}
