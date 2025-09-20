<?php

declare(strict_types=1);

namespace Tests\Resolvers\Strategies\Atomic;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;
use DemonDextralHorn\Resolvers\Strategies\Atomic\ParseSetCookieStrategy;

#[CoversClass(ParseSetCookieStrategy::class)]
final class ParseSetCookieStrategyTest extends TestCase
{
    private ParseSetCookieStrategy $parseSetCookieStrategy;
    private string $sessionCookieName;

    public function setUp(): void
    {
        parent::setUp();

        $this->sessionCookieName = config('demon-dextral-horn.defaults.session_cookie_name');
        $this->parseSetCookieStrategy = app(ParseSetCookieStrategy::class);
    }

    #[Test]
    public function it_returns_the_correct_session_cookie(): void
    {
        /* SETUP */
        $cookies = [
            'XSRF-TOKEN=abc123; Path=/; HttpOnly',
            'laravel_session=xyz789; Path=/; HttpOnly',
            'other_cookie=test; Path=/'
        ];
        $options = ['source_key' => $this->sessionCookieName];

        /* EXECUTE */
        $result = $this->parseSetCookieStrategy->handle(
            null,
            null,
            $options,
            $cookies
        );

        /* ASSERT */
        $this->assertSame('laravel_session=xyz789; Path=/; HttpOnly', $result);
    }

    #[Test]
    public function it_returns_null_when_session_cookie_not_found(): void
    {
        /* SETUP */
        $cookies = [
            'XSRF-TOKEN=abc123; Path=/; HttpOnly',
            'other_cookie=test; Path=/'
        ];
        $options = ['source_key' => 'laravel_session'];

        /* EXECUTE */
        $result = $this->parseSetCookieStrategy->handle(
            null,
            null,
            $options,
            $cookies
        );

        /* ASSERT */
        $this->assertNull($result);
    }

    #[Test]
    public function it_returns_null_when_value_is_null(): void
    {
        /* SETUP */
        $options = ['source_key' => 'laravel_session'];

        /* EXECUTE */
        $result = $this->parseSetCookieStrategy->handle(
            null,
            null,
            $options,
            null
        );

        /* ASSERT */
        $this->assertNull($result);
    }
}