<?php

declare(strict_types=1);

namespace Tests\Traits;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Arr;
use DemonDextralHorn\Traits\RequestParsingTrait;

/**
 * Test for RequestParsingTrait.
 * 
 * @class RequestParsingTraitTest
 */
final class RequestParsingTraitTest extends TestCase
{
    private $anonymousClass;

    public function setUp(): void
    {
        parent::setUp();

        // Create an anonymous class that uses the RequestParsingTrait
        $this->anonymousClass = new class {
            use RequestParsingTrait;

            // Since extractSessionCookieFromString is protected, we create a public method to access it for testing 
            public function callExtractSessionCookieFromString(?string $cookie)
            {
                return $this->extractSessionCookieFromString($cookie);
            }
        };
    }

    #[Test]
    public function it_succesfully_extracts_session_cookie_from_string(): void
    {
        /* SETUP */
        $sessionCookieString = config('demon-dextral-horn.defaults.session_cookie_name') . '=cookie_value; Path=/; HttpOnly; SameSite=Lax';

        /* EXECUTE */
        $sessionCookie = $this->anonymousClass->callExtractSessionCookieFromString($sessionCookieString);

        /* ASSERT */
        $this->assertIsArray($sessionCookie);
        $this->assertArrayHasKey('name', $sessionCookie);
        $this->assertArrayHasKey('value', $sessionCookie);
        $this->assertSame(config('demon-dextral-horn.defaults.session_cookie_name'), Arr::get($sessionCookie, 'name'));
        $this->assertSame('cookie_value', Arr::get($sessionCookie, 'value'));
    }

    #[Test]
    public function it_returns_null_if_cookie_not_found(): void
    {
        /* SETUP */
        $cookieString = 'another_cookie=abc123; Path=/; Secure';

        /* EXECUTE */
        $sessionCookie = $this->anonymousClass->callExtractSessionCookieFromString($cookieString);

        /* ASSERT */
        $this->assertNull($sessionCookie);
    }

    #[Test]
    public function it_handles_urlencoded_cookie_value(): void
    {
        /* SETUP */
        $cookieString = config('demon-dextral-horn.defaults.session_cookie_name') . '=my%20encoded%20value; Path=/; HttpOnly';

        /* EXECUTE */
        $sessionCookie = $this->anonymousClass->callExtractSessionCookieFromString($cookieString);

        /* ASSERT */
        $this->assertSame('my encoded value', Arr::get($sessionCookie, 'value'));
    }

    #[Test]
    public function it_ignores_flags_without_equals_sign_malformed_cookie_naming(): void
    {
        /* SETUP */
        $cookieString = config('demon-dextral-horn.defaults.session_cookie_name') . ':value123; HttpOnly; Secure; SameSite=Strict';

        /* EXECUTE */
        $sessionCookie = $this->anonymousClass->callExtractSessionCookieFromString($cookieString);

        /* ASSERT */
        $this->assertNull($sessionCookie);
    }

    #[Test]
    public function it_returns_null_on_empty_string(): void
    {
        /* EXECUTE */
        $sessionCookie = $this->anonymousClass->callExtractSessionCookieFromString('');

        /* ASSERT */
        $this->assertNull($sessionCookie);
    }

    #[Test]
    public function it_returns_null_when_cookie_string_is_null(): void
    {
        /* EXECUTE */
        $sessionCookie = $this->anonymousClass->callExtractSessionCookieFromString(null);

        /* ASSERT */
        $this->assertNull($sessionCookie);
    }
}
