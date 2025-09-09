<?php

declare(strict_types=1);

namespace Tests\Cache\Identifiers;

use DemonDextralHorn\Cache\Identifiers\SessionUserIdentifier;
use DemonDextralHorn\Data\TargetRouteData;
use DemonDextralHorn\Enums\AuthDriverType;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Http\Request;

/**
 * Test cases for SessionUserIdentifier.
 *
 * @class SessionUserIdentifierTest
 */
final class SessionUserIdentifierTest extends TestCase
{
    private SessionUserIdentifier $sessionUserIdentifier;

    public function setUp(): void
    {
        parent::setUp();

        $this->sessionUserIdentifier = app(SessionUserIdentifier::class);
    }

    #[Test]
    public function it_gets_identifier_for_session_user(): void
    {
        /* SETUP */
        $targetRouteData = new TargetRouteData(
            routeName: 'sample.route',
            method: Request::METHOD_GET,
            routeParams: ['id' => 2],
            queryParams: ['page' => 1],
            headers: [],
            cookies: [
                'session_cookie' => config('demon-dextral-horn.defaults.session_cookie_name') . '=cookie_value; Path=/; HttpOnly; SameSite=Lax'
            ]
        );

        /* EXECUTE */
        $identifier = $this->sessionUserIdentifier->getIdentifierFor($targetRouteData);

        /* ASSERT */
        $this->assertStringContainsString(AuthDriverType::SESSION->value, $identifier);
    }

    #[Test]
    public function it_gets_identifier_for_guest_user_when_no_session_cookie(): void
    {
        /* SETUP */
        $targetRouteData = new TargetRouteData(
            routeName: 'sample.route',
            method: Request::METHOD_GET,
            routeParams: ['id' => 2],
            queryParams: ['page' => 1],
            headers: [],
            cookies: [
                'some_other_cookie' => 'some_value'
            ]
        );

        /* EXECUTE */
        $identifier = $this->sessionUserIdentifier->getIdentifierFor($targetRouteData);

        /* ASSERT */
        $this->assertSame($this->sessionUserIdentifier::GUEST_USER, $identifier);
    }
}